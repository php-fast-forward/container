Agent skill
===========

The ``fast-forward-container`` skill teaches coding agents how to consume this
library: configuration-backed registration, factories, interface/scalar bindings,
test overrides, source precedence, standalone/delegated autowiring and cache
boundaries. Its portable entrypoint is
``resources/skills/fast-forward-container/SKILL.md``;
references, runnable examples and its MIT license travel with it.

Availability and prerequisites
------------------------------

The skill remains under review separately from the published runtime release
v1.9.1. Neither v1.9.0 nor v1.9.1 contains the skill directory. Until a later
release bundles it, use a reviewed repository checkout or an immutable Git
revision containing the skill.

All four examples target the published v1.9.1 API, PHP 8.3+ and Composer. The
provider-resolution example covers numeric IDs, method-factory targets,
class-extension identity and provider cycles corrected in that release. The
skill explains the Config ``^1.6`` requirement and precautions for older installs;
the other three examples also support v1.9.0 with compatible dependencies.
The agent checks the consumer's installed version before choosing APIs; a global
copy does not upgrade an application's dependencies. Installing the published
v1.9.1 runtime does not install this skill, and the skill must not prompt a
dependency upgrade to obtain itself.

After a release includes the directory, ``composer install`` makes its files
available under
``vendor/fast-forward/container/resources/skills/fast-forward-container``.
The library declares ``extra.skills.source`` as ``resources/skills`` and suggests
``llm/skills`` as an optional installer. The directory uses the plural
``resources`` convention, recognized by that tool. Composer alone makes the files
available; the consumer chooses automatic synchronization or an explicit copy.
Keep the complete skill directory so relative resource links remain valid.

Choose an installation scope
----------------------------

Prefer **project-local installation** for a team. Record the source
version/reference and review updates with the project's library version.
``composer.lock`` pins the installed library. With the optional plugin, commit
its configuration and review any changes to committed generated copies. With a
manual or Skills CLI copy, commit the installed skill alongside the application;
those copies do not refresh automatically. Use one installation mechanism for a
given skill so an automatic sync does not overwrite a manually maintained copy.

Use **global installation** when a developer wants the skill across projects.
That copy has an independent update lifecycle and must still inspect each
project's Composer version. Avoid duplicate local/global copies with conflicting
instructions; agent precedence varies, so keep one effective copy per host.

Discovery paths depend on the agent:

============  ===================  ======================
Agent         Project              Global
============  ===================  ======================
Codex         ``.agents/skills/``  ``~/.agents/skills/``
Claude Code   ``.claude/skills/``  ``~/.claude/skills/``
============  ===================  ======================

These are default paths; honor an agent's configured home and current discovery
rules. Other Agent Skills-compatible hosts may use different paths. The skill
itself has no dependency on a specific agent or discovery adapter.

Automatic project installation with Composer
--------------------------------------------

The optional `llm/skills <https://github.com/roxblnfk/skills>`_ Composer plugin
discovers the library's metadata and copies the complete skill directory into
the consumer project. It is suggested, not required: installing this library
does not install or authorize the plugin. The route below was exercised with
``llm/skills`` 1.15.0 on PHP 8.3 and 8.5.

First confirm that your installed library or reviewed checkout contains
``resources/skills/fast-forward-container/SKILL.md``. Published v1.9.1 does not
contain it. Before installing the plugin, create ``skills.json`` at the
application root so your choices apply to its first automatic sync:

.. code-block:: json

   {
     "target": ".agents/skills",
     "discovery": false,
     "auto-sync": true,
     "vendor-sources": false,
     "dependencies": {
       "composer": {
         "trusted": ["fast-forward/*"],
         "trusted-replace": true
       }
     }
   }

This enables declared skills from Fast Forward packages, including transitive
ones, without adding the tool's built-in or direct-dependency trust defaults.
Use ``fast-forward/container`` instead of ``fast-forward/*`` for a single-package
allowlist. ``vendor-sources: false`` disables remote skill sources advertised by
dependencies. Choose ``.claude/skills`` as the target for a Claude Code-only
project; the destination belongs to the application, not the library.

Then install the tool as a development dependency:

.. code-block:: sh

   composer require --dev llm/skills:^1.15
   composer skills:show

Review and authorize ``llm/skills`` when Composer asks. For non-interactive
installs, merge that specific authorization into the application's existing
``composer.json`` after approving the plugin:

.. code-block:: json

   {
     "config": {
       "allow-plugins": {
         "llm/skills": true
       }
     }
   }

Keep existing plugin authorizations and commit ``composer.json``,
``composer.lock`` and ``skills.json``. Subsequent ``composer install`` and
``composer update`` runs synchronize accepted skills automatically; no library
script or application hook is needed. ``--no-plugins`` prevents the plugin from
loading, and ``--no-scripts`` skips its automatic script-event hook. Set
``auto-sync`` to ``false`` to use only explicit commands:

.. code-block:: sh

   composer skills:update fast-forward/container --dry-run
   composer skills:update fast-forward/container

An explicit package name on that command also grants trust for that run, even
if the package is absent from the configured allowlist. The named command can
discover a skill without metadata; the automatic route above requires declared
metadata because ``discovery`` is disabled.

The plugin copies files and does not prove an agent has loaded the skill.
Inspect the result, refresh the agent session and confirm discovery. Installing
the Composer tool globally makes its commands available across projects; it
does not install this skill into the agent's global directory. Use the explicit
global copy or Skills CLI route below when that is the intended scope.

Automatic update and removal limits
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

An ordinary sync overwrites files supplied by the donor package, including local
edits to those same files. Additional local files survive. Treat donor files as
managed copies and change their source or choose a separately maintained copy
when customization is required.

The default merge does not delete files dropped upstream or skills left behind
by a removed dependency. Removing ``fast-forward/container`` or ``llm/skills``
therefore does not remove an already copied skill. A source path that is missing
or no longer accepted is also not proof that its old copy has disappeared.

The plugin offers cleanup, but scope matters:

.. code-block:: sh

   composer skills:update --clean --dry-run

An unscoped ``--clean`` can delete every skill directory in the target, including
handwritten ones; non-interactive runs proceed without a confirmation. A scoped
``--clean`` replaces skills that are still selected for that run and cannot
clean up a removed donor it no longer discovers. Do not add unscoped cleanup to
automatic hooks. For removal, identify the obsolete skill directory, move it to
a fresh backup outside all discovery paths using the copy-management procedure
below, then refresh the agent. Review the dry-run before any broader cleanup.

Copy from the installed library
------------------------------

From the consumer project root, select a source and one destination:

.. code-block:: sh

   skill_source="vendor/fast-forward/container/resources/skills/fast-forward-container"
   skill_target=".agents/skills/fast-forward-container"
   # Global Codex alternative:
   # skill_target="$HOME/.agents/skills/fast-forward-container"
   # For Claude Code use .claude/skills instead.

   test -f "$skill_source/SKILL.md" &&
   test ! -e "$skill_target" &&
   test ! -L "$skill_target" &&
   mkdir -p "$(dirname "$skill_target")" &&
   cp -R "$skill_source" "$skill_target"

The command refuses an existing destination, including a dangling symlink. A
missing source means the installed release predates the bundled skill: select a
reviewed checkout and set ``skill_source`` to its skill directory instead. Do not
create a placeholder ``SKILL.md`` or change production dependencies to install
agent instructions.

Inspect the copied entrypoint and resources, then refresh the agent session and
confirm the skill appears in its available skills. For an explicit activation,
ask the agent to use ``fast-forward-container`` to configure the application.
Installation alone does not prove that an agent has loaded the skill.

Update, removal and rollback for a copy
--------------------------------------

Record the new source version/reference and review the diff before an update.
Move the old package to a fresh backup path **outside every skill discovery
directory**, then repeat the guarded copy command. For example, after choosing
the same ``skill_target``:

.. code-block:: sh

   skill_backup="$HOME/.agent-skill-backups/fast-forward-container-$(date +%Y%m%d%H%M%S)-$$"
   test ! -e "$skill_backup" &&
   mkdir -p "$(dirname "$skill_backup")" &&
   mv "$skill_target" "$skill_backup"

Moving it out also removes a manually installed copy from discovery. Refresh the
agent session. To roll back, archive any replacement first and move the saved
directory back to its original destination. Keep ``LICENSE`` and all references
with the package; do not archive old copies inside ``.agents/skills`` or
``.claude/skills``, where they may remain discoverable.

Assisted installation with Skills CLI
-------------------------------------

The optional `Skills CLI <https://github.com/vercel-labs/skills>`_ supports both
scopes and agent-specific paths. The following commands were verified with
``skills@1.7.2`` and Node.js 22.23.3; that CLI requires Node.js 22.20.0+.
Repository-source installation also requires Git. The CLI is a development tool,
not a PHP runtime dependency.

For a bundled source in the current project:

.. code-block:: sh

   npx --yes skills@1.7.2 add ./vendor/fast-forward/container/resources/skills/fast-forward-container --skill fast-forward-container --agent codex --copy --yes
   npx --yes skills@1.7.2 list --agent codex

Add ``--global`` to ``add`` and ``list`` for a global installation. Replace
``--agent codex`` with ``--agent claude-code`` for Claude Code. The explicit
``--copy`` avoids symlinks into a vendor directory that Composer may replace.

Before the first skill-bearing release, or to pin instructions separately, set
``SKILL_REF`` to a **reviewed full commit SHA containing the package**, then use:

.. code-block:: sh

   npx --yes skills@1.7.2 add "https://github.com/php-fast-forward/container/tree/$SKILL_REF/resources/skills/fast-forward-container" --skill fast-forward-container --agent codex --copy --yes

Record that SHA with the application. A branch name can move. To update a pinned
installation, repeat ``add`` with the newly reviewed source/revision and inspect
the resulting diff. An unqualified update-to-latest is not the same as a pinned
upgrade. Commit project copies and any lockfile generated by the CLI.

Remove only this skill with:

.. code-block:: sh

   npx --yes skills@1.7.2 remove fast-forward-container --agent codex --yes
   # For the global installation:
   npx --yes skills@1.7.2 remove fast-forward-container --agent codex --global --yes

Other Composer tooling
-----------------------

`netresearch/composer-agent-skill-plugin <https://github.com/netresearch/composer-agent-skill-plugin>`_
can discover library-provided metadata or explicitly configured direct sources,
manage trust and generate an ``AGENTS.md`` index. This is a useful option for teams
already choosing that workflow, but it executes as a Composer plugin and changes
project instruction files. It is not required by this library and no automatic
registration hook is enabled here.

The published 2.1.1 manifest requires PHP 8.4, so it does not cover the full PHP
8.3 range supported here. If a project adopts it separately, review its version,
allow-plugins/trust policy and generated-instruction ownership. This guide does
not claim an operational test of that separate integration.

Validation scope
-----------------

The skill examples are tested as PHP consumer programs. The optional
``llm/skills`` path was checked with isolated Composer consumers, including
archive payload hashes, automatic sync, repeated installs, overwritten local
edits and stale copies after dependency removal. The broader cleanup mode was
checked with a dry-run. Installation, pinned
reinstallation, listing and removal were exercised for Codex and Claude Code paths
inside an isolated container. This checks file distribution and CLI discovery,
not live model behavior in every agent. No developer's global skills are changed
by repository tests. See the skill's bundled references for the API baseline.
