Agent skill
===========

The ``fast-forward-container`` skill teaches coding agents how to consume this
library: configuration-backed registration, factories, interface/scalar bindings,
test overrides, source precedence, standalone/delegated autowiring and cache
boundaries. Its portable entrypoint is ``skills/fast-forward-container/SKILL.md``;
references, runnable examples and its MIT license travel with it.

Availability and prerequisites
------------------------------

The skill was added after v1.9.0. That release does not contain the skill directory.
Until a release bundles it, use a reviewed repository checkout or an immutable Git
revision containing the skill. The API examples target the published v1.9.0 API,
PHP 8.3+ and Composer. The agent checks the consumer's installed version before
choosing APIs; a global copy does not upgrade an application's dependencies.

After a release includes the directory, ``composer install`` makes its files
available under ``vendor/fast-forward/container/skills/fast-forward-container``.
Composer does not activate the skill automatically. Copying the whole directory
keeps its relative resource links valid.

Choose an installation scope
----------------------------

Prefer **project-local installation** for a team: review and commit the installed
skill alongside the application, record its source version/reference, and update
it deliberately with the project's library version. ``composer.lock`` pins the
library source; the copied instructions do not refresh automatically.

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

Copy from the installed library
------------------------------

From the consumer project root, select a source and one destination:

.. code-block:: sh

   skill_source="vendor/fast-forward/container/skills/fast-forward-container"
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

   npx --yes skills@1.7.2 add ./vendor/fast-forward/container/skills/fast-forward-container --skill fast-forward-container --agent codex --copy --yes
   npx --yes skills@1.7.2 list --agent codex

Add ``--global`` to ``add`` and ``list`` for a global installation. Replace
``--agent codex`` with ``--agent claude-code`` for Claude Code. The explicit
``--copy`` avoids symlinks into a vendor directory that Composer may replace.

Before the first skill-bearing release, or to pin instructions separately, set
``SKILL_REF`` to a **reviewed full commit SHA containing the package**, then use:

.. code-block:: sh

   npx --yes skills@1.7.2 add "https://github.com/php-fast-forward/container/tree/$SKILL_REF/skills/fast-forward-container" --skill fast-forward-container --agent codex --copy --yes

Record that SHA with the application. A branch name can move. To update a pinned
installation, repeat ``add`` with the newly reviewed source/revision and inspect
the resulting diff. An unqualified update-to-latest is not the same as a pinned
upgrade. Commit project copies and any lockfile generated by the CLI.

Remove only this skill with:

.. code-block:: sh

   npx --yes skills@1.7.2 remove fast-forward-container --agent codex --yes
   # For the global installation:
   npx --yes skills@1.7.2 remove fast-forward-container --agent codex --global --yes

Composer plugin evaluation
--------------------------

`netresearch/composer-agent-skill-plugin <https://github.com/netresearch/composer-agent-skill-plugin>`_
can discover library-provided metadata or explicitly configured direct sources,
manage trust and generate an ``AGENTS.md`` index. This is a useful option for teams
already choosing that workflow, but it executes as a Composer plugin and changes
project instruction files. It is not required by this library and no automatic
registration hook is enabled here.

The supported defaults are explicit project/global copies, with Skills CLI as an
assisted alternative. A dedicated installer package or automatic Composer
integration adds lifecycle behavior without being needed for these routes. If
a project adopts that plugin separately, review its version, allow-plugins/trust
policy and generated-instruction ownership; use its current direct-source
documentation. This PR does not claim that plugin integration has been executed.

Validation scope
-----------------

The skill examples are tested as PHP consumer programs. Installation, pinned
reinstallation, listing and removal were exercised for Codex and Claude Code paths
inside an isolated container. This checks file distribution and CLI discovery,
not live model behavior in every agent. No developer's global skills are changed
by repository tests. See the skill's bundled references for the API baseline.
