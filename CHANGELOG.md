# Changelog

Notable changes to `fast-forward/container` are documented here, newest first.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and version numbers follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Historical entries were backfilled from tagged diffs and GitHub release notes.
Dates follow GitHub release publication in the `America/Sao_Paulo` timezone.

## [1.10.3] - 2026-10-20

### Changed

- Restore the Changelog contributor skill through the development-only Composer skill manager and its reviewed GitHub source instead of versioning generated skill copies. ([#45](https://github.com/php-fast-forward/container/pull/45))
- Synchronize the Config consumer skill from the installed Composer dependencies through an explicit trusted-package list, preserving other configured skill sources. ([#47](https://github.com/php-fast-forward/container/pull/47))

## [1.10.2] - 2026-10-20

### Changed

- Refresh the Fast Forward Changelog contributor skill to 0.3.0 and show the repository name and version transition in managed release PR titles. ([#42](https://github.com/php-fast-forward/container/pull/42))

## [1.10.1] - 2026-10-18

### Added

- Includes the release-owned Fast Forward Changelog project skill with integrity receipts and contributor guidance for installation, verification and deliberate updates. ([#40](https://github.com/php-fast-forward/container/pull/40))

### Changed

- Uses independent changelog fragments and automated version PRs, with verified PR references and normalized historical release notes. Tag and GitHub Release publication remains disabled pending explicit approval. ([#38](https://github.com/php-fast-forward/container/pull/38))
- Triggers the test workflow on pushes only when PHP files or Composer configuration change. Manual workflow execution remains available. ([#41](https://github.com/php-fast-forward/container/pull/41))

## [1.10.0] - 2026-10-16

### Added

- Repository contributor instructions covering public behavior, development checks and publication boundaries. ([#36](https://github.com/php-fast-forward/container/pull/36))
- A portable `fast-forward-container` agent skill with configuration, composition and autowiring examples, version-aware guidance, and documented project/global installation options. ([#28](https://github.com/php-fast-forward/container/pull/28))
- Optional `llm/skills` discovery metadata and Composer suggestion for automatic project synchronization, with documented update and removal limits. The skill is bundled under `resources/skills`. ([#28](https://github.com/php-fast-forward/container/pull/28))

## [1.9.1] - 2026-10-16

### Changed

- Require FastForward Config 1.6 or newer within 1.x, using its native configuration storage and removing the transitive `dflydev/dot-access-data` dependency. ([#29](https://github.com/php-fast-forward/container/pull/29))
- `MethodFactory` directly constructs a class-name target only when `has()` reports it absent. Existing target construction and execution failures, including nested not-found errors, now propagate instead of silently using an unconfigured replacement. ([#33](https://github.com/php-fast-forward/container/pull/33))

### Fixed

- Detect circular provider factory, alias and extension resolution with an actionable path per execution context, preserving ordered fallback, shared caches and retry cleanup after failures. Keep concurrent Fiber paths independent from each other and from the main context. ([#34](https://github.com/php-fast-forward/container/pull/34))
- Resolve `MethodFactory` service-ID targets before reflecting their instance methods, preserving configured constructor dependencies and method visibility checks. ([#33](https://github.com/php-fast-forward/container/pull/33))
- Correct typed callable factory examples, Slim 4 application creation and JSON responses, and Laravel 12 factory binding adapters. Explain framework requirements, binding lifetimes and provider extension boundaries. ([#30](https://github.com/php-fast-forward/container/pull/30))
- Reject incompatible dependency combinations by requiring PSR Container 1.1 or 2.x and a compatible FastForward Config version, with production consumer checks for the minimum and current versions. ([#29](https://github.com/php-fast-forward/container/pull/29))
- Preserve numeric-looking service identifiers when aggregating provider factories, retaining later-provider precedence and matching factory/extension keys. ([#31](https://github.com/php-fast-forward/container/pull/31))
- Apply concrete-class extensions to distinct services independently of lookup order, reusing each object's completed extension result for aliases and wrapper-backed lookups, including replacement objects, scalars and null. ([#32](https://github.com/php-fast-forward/container/pull/32))

## [1.9.0] - 2026-10-16

### Added

- Native constructor autowiring with recursive dependency resolution, shared instances, cycle diagnostics and cleanup after failed construction. ([#18](https://github.com/php-fast-forward/container/pull/18))
- Standalone autowiring through `new AutowireContainer()`, with an optional nullable PSR-11 delegate whose entries take precedence over autowiring. ([#18](https://github.com/php-fast-forward/container/pull/18))
- Support for constructor parameters passed by reference and optional internal arguments whose default values are unavailable through reflection. ([#18](https://github.com/php-fast-forward/container/pull/18))
- Named exception factories for circular dependency paths and unresolvable constructor parameters. ([#18](https://github.com/php-fast-forward/container/pull/18))
- A versioned changelog, including the release history from `1.0.0` through `1.8.0`. ([#18](https://github.com/php-fast-forward/container/pull/18))

### Changed

- Optional named object dependencies resolve explicit container entries or autowire concrete classes before using their declared defaults, including non-null defaults. ([#18](https://github.com/php-fast-forward/container/pull/18))
- Missing entries and structurally unavailable dependency graphs use an optional argument's default. Autoloader, constructor, factory, type, cycle and default-expression failures remain errors with their original causes preserved. ([#18](https://github.com/php-fast-forward/container/pull/18))
- Aggregate lookups still try later sources after delegated failures, but preserve the first construction exception when no source succeeds instead of reporting a fresh not-found exception. ([#18](https://github.com/php-fast-forward/container/pull/18))
- `AutowireContainer::has()` checks whether an entry is known without calling `get()`. Known classes or entries may return `true` even when construction fails; unknown entries return `false` and `get()` throws `NotFoundExceptionInterface`, following PSR-11. Applications that used `has()` as a construction health check must call `get()` and handle resolution errors instead. ([#18](https://github.com/php-fast-forward/container/pull/18))

- The public `AutowireContainer`, `ContainerBuilder`, `ContainerFactory` and `container()` APIs remain available. PHP `^8.3` remains the minimum requirement. ([#18](https://github.com/php-fast-forward/container/pull/18))
- Optional dependencies may now execute constructors that were previously skipped. A nullable argument without a default remains required. ([#18](https://github.com/php-fast-forward/container/pull/18))
- Applications that use PHP-DI directly must declare their own dependency. External PHP-DI containers remain usable as PSR-11 sources. ([#18](https://github.com/php-fast-forward/container/pull/18))
- Scalars, ambiguous union/intersection/DNF types and variadic collections require defaults or explicit service factories. See the [autowiring guide](https://github.com/php-fast-forward/container/blob/v1.9.0/docs/advanced/autowire.rst) for the complete contract. ([#18](https://github.com/php-fast-forward/container/pull/18))

### Removed

- The runtime `php-di/php-di` dependency and the internal PHP-DI autowiring engine. ([#18](https://github.com/php-fast-forward/container/pull/18))

## [1.8.0] - 2026-10-12

### Added

- Incremental container composition through fluent `ContainerBuilder::add()` and independent `build()` calls. ([#16](https://github.com/php-fast-forward/container/pull/16))
- Documentation and a runnable example for conditional registration across multiple setup stages. ([#16](https://github.com/php-fast-forward/container/pull/16))
- Fluent `add(...$initializers)` accepts the same configuration objects, PSR-11 containers, service providers and class strings as `ContainerFactory::create()`. ([#16](https://github.com/php-fast-forward/container/pull/16))

### Changed

- `ContainerFactory::create()` delegates to the builder, retaining one implementation of container assembly and preserving initializer order, source reuse and existing lifetimes. ([#16](https://github.com/php-fast-forward/container/pull/16))
- Each `build()` creates fresh aggregate, provider/config adapters and autowiring state. Later registrations affect future builds only; supplied source objects are reused and class-string initializers are instantiated anew for each build. Cross-source dependencies, lazy resolution and optional nested configuration handling are preserved. ([#16](https://github.com/php-fast-forward/container/pull/16))

## [1.7.1] - 2026-10-12

### Removed

- The obsolete Wiki submodule pointer; the Wiki repository and its history remain separate. ([#15](https://github.com/php-fast-forward/container/pull/15))

### Fixed

- GitHub Wiki generation and direct publication using the shared PHP/Composer tooling and a consistently pinned renderer. ([#15](https://github.com/php-fast-forward/container/pull/15))
- Compatibility with the GrumPHP shim required by the development tooling. ([#15](https://github.com/php-fast-forward/container/pull/15))

## [1.7.0] - 2026-10-11

### Added

- Static composition through `ContainerFactory::create()`, sharing its assembly path with `container()`. ([#12](https://github.com/php-fast-forward/container/pull/12))
- Runnable provider/factory examples and expanded API, onboarding and integration documentation. ([#12](https://github.com/php-fast-forward/container/pull/12))

### Changed

- Shared test-status publication and Dependabot status handling. ([#8](https://github.com/php-fast-forward/container/pull/8))
- Native Pages reports and documentation publication. ([#9](https://github.com/php-fast-forward/container/pull/9))
- Contextual repository illustration. ([#7](https://github.com/php-fast-forward/container/pull/7))
- Documentation presentation and package/archive hygiene.

### Fixed

- Extension-only entries, including discovery without executing factories or extensions. ([#12](https://github.com/php-fast-forward/container/pull/12))
- Extension return values replacing the previous service, including `null`, while explicitly `void` extensions retain the previous value for compatibility. ([#12](https://github.com/php-fast-forward/container/pull/12))
- Cached null entries, concrete-class alias precedence and extension composition across providers. ([#12](https://github.com/php-fast-forward/container/pull/12))
- Preservation of provider discovery/execution failure causes, including textual exception codes on the previous exception. ([#12](https://github.com/php-fast-forward/container/pull/12))

### Security

- Ignore Composer CI environment dump files to prevent accidental inclusion in commits and distributions. ([#10](https://github.com/php-fast-forward/container/pull/10))

## [1.6.0] - 2026-03-27

### Added

- Factories exposing each aggregated provider instance under its concrete class identifier.
- Topic-based documentation covering composition, providers, factories, configuration and framework integration.

### Changed

- Minimum supported PHP version from `^8.1` to `^8.3`. ([#5](https://github.com/php-fast-forward/container/pull/5))
- Development checks and reports use `fast-forward/dev-tools`. ([#5](https://github.com/php-fast-forward/container/pull/5))
- Wiki layout, publication workflows and test organization. ([#5](https://github.com/php-fast-forward/container/pull/5))

## [1.5.1] - 2025-08-01

### Added

- Generated API documentation and GitHub Actions automation for tests and Wiki publication.

### Fixed

- `AggregateServiceProvider` registers its own factory using the concrete static class, supporting subclasses.

## [1.5.0] - 2025-07-01

### Changed

- `MethodFactory` retrieves its target object from the container before falling back to direct instantiation for non-static methods.

## [1.4.1] - 2025-06-11

### Fixed

- `ServiceProviderContainer` caches object services under both the requested identifier and their concrete class when the class alias is not already cached.

## [1.4.0] - 2025-06-11

### Added

- Service extensions can target an object's concrete class as well as its original service identifier.

### Fixed

- `CallableFactory` invokes the callable with its resolved arguments without rebinding the closure to the container.

## [1.3.0] - 2025-05-24

### Changed

- `CallableFactory` resolves callable parameters from their class/interface types instead of passing the container as the sole argument.

### Fixed

- Unsupported untyped or builtin callable parameters raise an explicit invalid-parameter exception.

## [1.2.1] - 2025-05-06

### Fixed

- Avoid eager target-class lookup in `MethodFactory`, preventing a possible circular dependency during method-factory resolution.
- Correct the initializer documentation for `container()`.

## [1.2.0] - 2025-05-05

### Added

- `ContainerInterface`, `AutowireContainer` and service-provider adapters with factory and extension support.
- `ArrayServiceProvider` and `AggregateServiceProvider` for provider composition.
- Alias, callable, invokable, method and existing-service factory helpers, plus container-specific exception types.

### Changed

- `container()` accepts variadic configuration objects, PSR-11 containers, service providers and initializer class names, composing them with an autowiring fallback.

## [1.1.0] - 2025-04-20

### Added

- `AggregateContainer::append()` and `prepend()` for extending or prioritizing container sources after construction.

## [1.0.0] - 2025-04-20

### Added

- Initial PSR-11 aggregate container with ordered delegation, caching and missing-entry exceptions.
- Configuration-backed composition and PHP-DI autowiring through `container()` and the original invokable `Factory\ContainerFactory`.
- Unit tests, Composer autoloading and the MIT license.

[1.10.3]: https://github.com/php-fast-forward/container/compare/v1.10.1...v1.10.3
[1.10.2]: https://github.com/php-fast-forward/container/compare/v1.10.1...v1.10.2
[1.10.1]: https://github.com/php-fast-forward/container/compare/v1.10.0...v1.10.1
[1.10.0]: https://github.com/php-fast-forward/container/compare/v1.9.1...v1.10.0
[1.9.1]: https://github.com/php-fast-forward/container/compare/v1.9.0...v1.9.1
[1.9.0]: https://github.com/php-fast-forward/container/compare/v1.8.0...v1.9.0
[1.8.0]: https://github.com/php-fast-forward/container/compare/v1.7.1...v1.8.0
[1.7.1]: https://github.com/php-fast-forward/container/compare/v1.7.0...v1.7.1
[1.7.0]: https://github.com/php-fast-forward/container/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/php-fast-forward/container/compare/v1.5.1...v1.6.0
[1.5.1]: https://github.com/php-fast-forward/container/compare/v1.5.0...v1.5.1
[1.5.0]: https://github.com/php-fast-forward/container/compare/v1.4.1...v1.5.0
[1.4.1]: https://github.com/php-fast-forward/container/compare/v1.4.0...v1.4.1
[1.4.0]: https://github.com/php-fast-forward/container/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/php-fast-forward/container/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/php-fast-forward/container/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/php-fast-forward/container/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/php-fast-forward/container/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/php-fast-forward/container/releases/tag/v1.0.0
