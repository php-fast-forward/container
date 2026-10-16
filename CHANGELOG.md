# Changelog

Notable changes to `fast-forward/container` are documented here, newest first.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and version numbers follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Historical entries were backfilled from tagged diffs and GitHub release notes.
Dates follow GitHub release publication in the `America/Sao_Paulo` timezone.

## [Unreleased]

### Fixed

- Apply concrete-class extensions to distinct services independently of lookup order, while avoiding repeated extension of an alias to the cached class instance.

## [1.9.0] - 2026-10-16

### Added

- Native constructor autowiring with recursive dependency resolution, shared instances, cycle diagnostics and cleanup after failed construction.
- Standalone autowiring through `new AutowireContainer()`, with an optional nullable PSR-11 delegate whose entries take precedence over autowiring.
- Support for constructor parameters passed by reference and optional internal arguments whose default values are unavailable through reflection.
- Named exception factories for circular dependency paths and unresolvable constructor parameters.
- A versioned changelog, including the release history from `1.0.0` through `1.8.0`.

### Changed

- Optional named object dependencies resolve explicit container entries or autowire concrete classes before using their declared defaults, including non-null defaults.
- Missing entries and structurally unavailable dependency graphs use an optional argument's default. Autoloader, constructor, factory, type, cycle and default-expression failures remain errors with their original causes preserved.
- Aggregate lookups still try later sources after delegated failures, but preserve the first construction exception when no source succeeds instead of reporting a fresh not-found exception.
- `AutowireContainer::has()` checks whether an entry is known without calling `get()`. Known classes or entries may return `true` even when construction fails; unknown entries return `false` and `get()` throws `NotFoundExceptionInterface`, following PSR-11. Applications that used `has()` as a construction health check must call `get()` and handle resolution errors instead.

- The public `AutowireContainer`, `ContainerBuilder`, `ContainerFactory` and `container()` APIs remain available. PHP `^8.3` remains the minimum requirement.
- Optional dependencies may now execute constructors that were previously skipped. A nullable argument without a default remains required.
- Applications that use PHP-DI directly must declare their own dependency. External PHP-DI containers remain usable as PSR-11 sources.
- Scalars, ambiguous union/intersection/DNF types and variadic collections require defaults or explicit service factories. See the [autowiring guide](docs/advanced/autowire.rst) for the complete contract.

### Removed

- The runtime `php-di/php-di` dependency and the internal PHP-DI autowiring engine.

Delivered through [PR #18](https://github.com/php-fast-forward/container/pull/18), addressing [issue #17](https://github.com/php-fast-forward/container/issues/17).

## [1.8.0] - 2026-10-12

### Added

- Incremental container composition through fluent `ContainerBuilder::add()` and independent `build()` calls.
- Documentation and a runnable example for conditional registration across multiple setup stages.

### Changed

- `ContainerFactory::create()` delegates to the builder, retaining one implementation of container assembly and preserving initializer order, source reuse and existing lifetimes.

## [1.7.1] - 2026-10-12

### Removed

- The obsolete Wiki submodule pointer; the Wiki repository and its history remain separate.

### Fixed

- GitHub Wiki generation and direct publication using the shared PHP/Composer tooling and a consistently pinned renderer.
- Compatibility with the GrumPHP shim required by the development tooling.

## [1.7.0] - 2026-10-11

### Added

- Static composition through `ContainerFactory::create()`, sharing its assembly path with `container()`.
- Runnable provider/factory examples and expanded API, onboarding and integration documentation.

### Changed

- Shared test-status and Pages reporting workflows, documentation presentation and package/archive hygiene.

### Fixed

- Extension-only entries, including discovery without executing factories or extensions.
- Extension return values replacing the previous service, including `null`, while explicitly `void` extensions retain the previous value for compatibility.
- Cached null entries, concrete-class alias precedence and extension composition across providers.
- Preservation of provider discovery/execution failure causes, including textual exception codes on the previous exception.

## [1.6.0] - 2026-03-27

### Added

- Factories exposing each aggregated provider instance under its concrete class identifier.
- Topic-based documentation covering composition, providers, factories, configuration and framework integration.

### Changed

- Minimum supported PHP version from `^8.1` to `^8.3`.
- Development checks and reports use `fast-forward/dev-tools`.
- Wiki layout, publication workflows and test organization.

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
