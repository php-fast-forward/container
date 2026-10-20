# Fast Forward Container: contributor instructions

## Project and boundaries

`fast-forward/container` is a PHP library for PSR-11 container composition,
service providers, configuration and native constructor autowiring. Read the
[README](README.md) for the public API and the [guides](docs/index.rst) for behavior.

- `src/`: runtime code under `FastForward\Container`; `src/functions.php` contains
  the Composer-loaded helper functions.
- `ContainerBuilder`, `ContainerFactory` and `container(...)` compose sources in
  registration order. Supplied container objects are reused across builds.
- `AggregateContainer`, `ServiceProviderContainer` and `AutowireContainer` own
  their respective lookup, provider and autowiring behavior.
  `src/Autowire/ReflectionContainer.php` is an internal implementation detail.
- `tests/`: PHPUnit regression tests; `tests/Fixtures/` and
  `tests/Integration/Fixture/` hold named test collaborators.
- `examples/` contains runnable usage examples; `docs/` contains maintained
  reStructuredText guides and API explanations.

Preserve documented precedence, shared instances, null entries and PSR-11
exception boundaries. Autowiring tries resolvable optional object dependencies
before defaults; construction failures and cycles remain errors when resolution
exhausts its alternatives. Consult [the autowiring guide](docs/advanced/autowire.rst)
and [error reporting](docs/advanced/error-reporting.rst) before changing these paths.

## Bundled consumer skill

Read [fast-forward-container](resources/skills/fast-forward-container/SKILL.md)
when writing usage examples, application integrations, consumer-facing guidance
or the bundled skill itself.
It defines the supported library versions, configuration patterns, entry points,
precedence, factories and autowiring contracts. Follow this contributor document
for library implementation and maintenance work.

Keep the skill, its bundled references and executable examples aligned with the
public API. Write instructions for the supported publication contract, without
temporary PR status or development history. Keep the complete package under
`resources/skills/fast-forward-container/`; document installation and copy lifecycle
in [the installation guide](docs/advanced/agent-skill.rst).

## Setup and tooling

Use Composer 2. Runtime PHP compatibility is declared in [composer.json](composer.json)
as `^8.3`; `config.platform.php` is the dependency-resolution floor, not proof that
the development tools run on every supported runtime.

```sh
composer install
composer validate --strict
composer dev-tools -- list
```

The development toolchain comes from `fast-forward/dev-tools:dev-main`.
`composer.lock` is ignored, so record the installed DevTools revision when reporting
tool-dependent results. Use the existing Composer scripts and packaged
configurations instead of adding duplicate PHPUnit or style configurations.
Composer plugins may synchronize development files; inspect the worktree after
installation or synchronization and keep unrelated generated changes out of the PR.

## Validation

Run focused tests while developing, then the complete suite for runtime changes:

```sh
composer dev-tools -- tests --filter=AutowireContainerTest
composer dev-tools -- tests
composer dev-tools -- tests --coverage=.dev-tools/coverage --min-coverage=80
```

The coverage command needs Xdebug coverage mode or PCOV enabled. DevTools supplies
the PHPUnit configuration and bootstrap, including support for mocking final
classes. Tests use PHPUnit attributes, coverage metadata and Prophecy where
appropriate. Add regression cases for changed behavior and error paths; preserve
strict checks for warnings, risky tests and unexpected output.

For PHP changes, inspect the checks available through
`composer dev-tools -- standards --help` and
`composer dev-tools -- code-style --help`. The `--` forwards options to DevTools.
`composer dev-tools` runs the standards/report composite;
`composer dev-tools:fix` enables automatic fixes.
Review their scope first: they can generate reports, update the local lockfile or
propose unrelated baseline changes. Report any failing check and its scope instead
of silently including a repository-wide migration.

[tests.yml](.github/workflows/tests.yml) calls the shared DevTools suite and adds
isolated production consumers using minimum and current dependencies. Its
production checks install PHPUnit separately so development dependencies cannot
hide missing runtime requirements. When changing dependencies or composition,
verify those jobs as well as the unit suite on the current PR commit.

## Code and test organization

- Follow [.editorconfig](.editorconfig), existing PHP style and the shared DevTools
  configuration. Runtime code must remain compatible with PHP 8.3.
- Keep one named class, interface or trait per file, with the matching filename
  and PSR-4 namespace. This includes test doubles, stubs and integration fixtures;
  put collaborators in separate fixture files instead of appending them to a test.
- Prefer small methods with explicit responsibilities and descriptive names.
  Use early returns to simplify branches and extract meaningful operations when
  a method becomes difficult to follow. Preserve behavior in readability refactors.
- Maintain the license header and useful PHPDoc on changed declarations. Use the
  existing exception factories for known failure cases and preserve their causes.
- Keep tests isolated and deterministic. Test production dependency behavior
  through an isolated consumer rather than relying on packages from DevTools.

## Documentation, distribution and pull requests

Use clear English in repository documentation, code and PR descriptions. Update
the relevant guide and examples when public behavior changes; keep instructions
specific and avoid duplicating the README.

[reports.yml](.github/workflows/reports.yml) generates reports and documentation.
[wiki.yml](.github/workflows/wiki.yml) renders API pages on PRs and publishes them
from `main`; edit their source in `src/` and `docs/`, not generated API pages.
Keep generated reports, caches, `vendor/` and the local lockfile out of commits.
Check [.gitattributes](.gitattributes) when changing files shipped in Composer archives.

Record user-visible changes in one independent `.changelog/*.md` fragment using
the schema and commands in [the changelog guide](docs/changelog.md). Preserve
inherited fragments and published history in [CHANGELOG.md](CHANGELOG.md).
Read the installed [Fast Forward Changelog skill](.agents/skills/fast-forward-changelog/SKILL.md)
before changelog contribution, version-review or maintenance work.
Do not add an `Unreleased` section or require a follow-up fragment edit for a PR
number: version preparation resolves verified delivery PRs automatically.
The Changelog workflow prepares a reviewable version PR. Merging the approved
managed version PR authorizes automatic publication of its exact version tag
and GitHub Release; the workflow never merges that PR itself. Historical release
recovery and changes to existing Releases require separate explicit authorization.
Central-history maintenance requires the library's verified human maintain/admin
grant; a label alone or successful CI is not approval to publish.

Keep each PR focused. Its title and description must describe the final change,
explain relevant behavior and list actual validation and limitations. Recheck the
current commit, CI results and open review threads after pushing; do not treat an
older review as validation of a newer commit.
