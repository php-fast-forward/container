# Versions and failures

The API baseline is published `fast-forward/container` **v1.9.1**, PHP 8.3+. The skill is still under review; neither v1.9.0 nor v1.9.1 bundles this directory. Use a reviewed skill-bearing checkout/revision until a later release includes it. Compare `composer.lock`, `composer show` and installed source. A constraint alone does not establish the installed version. Preserve the project's dependency policy; do not silently upgrade it.

## Corrections released in 1.9.1

Release v1.9.1 was published on 2026-10-16 with the following fixes. Confirm the consumer's installed revision before relying on them; copying this skill does not upgrade its dependencies.

| Area | Released 1.9.1 behavior | Older 1.9.0 precaution |
| --- | --- | --- |
| Runtime dependencies | Config `^1.6`, PSR Container `^1.1` or `^2.0`; production minimum checks derive from Composer constraints with `--prefer-lowest` | Check resolved versions explicitly as described below |
| Aggregated numeric IDs | Preserve opaque IDs, matching extensions and later-provider overrides | Prefer descriptive IDs or separate sources; numeric keys may be renumbered |
| Method factories | Resolve instance service IDs/interfaces; reflect the actual object; preserve registered-target failures | Use reflectable class names or explicit closures |
| Class extensions | Apply independently to distinct instances and reuse completed results for the same identity | Prefer ID-specific extensions when lookup order or aliases matter |
| Provider recursion | Detect factory/alias/extension cycles, clean up retries and isolate active Fiber paths | Keep provider graphs acyclic; only constructor cycles have the native guard |

See [composition and factories](composition.md) for the complete contracts. All four examples target published 1.9.1; `examples/provider-resolution.php` requires its provider corrections, while the other three also cover the shared 1.9.0 API.

Also inspect `composer show psr/container`. v1.9.0's declared range allows PSR container 1.0.0, but that interface's untyped signatures conflict with the library's typed methods. PSR container 1.1.x or 2.x avoids that startup failure; report incompatible constraints and resolve them deliberately rather than silently updating the consumer. See [issue #20](https://github.com/php-fast-forward/container/issues/20).

For configuration-backed composition, inspect `composer show fast-forward/config` too. Versions before 1.1.4 have an older `ConfigContainer` prefix contract and do not implement the documented `config.*`/nested-provider route correctly ([issue #27](https://github.com/php-fast-forward/container/issues/27)). The core examples were exercised with config 1.5.1 on 1.9.0; the 1.9.1 baseline requires Config `^1.6`. Config 1.6 removes the transitive `dflydev/dot-access-data` dependency through native storage. Verify the actual runtime's platform requirements and mbstring functions/compatible polyfill, especially when maintaining older installs.

Before 1.9.0, the internal engine used PHP-DI, an `AutowireContainer` delegate was required and `has()` eagerly attempted construction. Do not promise native optional-before-default behavior there. Explicit provider factories are the version-inspectable alternative. PHP-DI DSL, attributes, proxies and compilation are outside this library's native engine; direct PHP-DI consumers must declare their dependency.

## Native constructor outcomes since 1.9.0

| Dependency/parameter | Outcome |
| --- | --- |
| Named object with explicit entry | Try explicit entries before native autowiring |
| Constructible concrete named object | Recursively build and cache on success |
| Optional object absent or graph structurally uninferable | Use declared default, including non-null defaults |
| Constructor/factory/autoloader/type/default-expression failure or cycle from a source's `get()` | PSR-11 errors permit later sources; propagate the first construction error if none succeeds |
| Source `has()` failure, including unreadable provider metadata | Propagate immediately without trying later sources |
| Nullable parameter without default | Required; nullable does not mean omittable |
| Required scalar, ambiguous union/intersection/DNF | Explicit factory for the containing service |
| Variadic collection | No automatic element discovery; supply elements explicitly |
| Optional internal argument without reflected default | Omit it when reflection permits omission |

Interfaces are not automatically mapped to arbitrary implementations. A concrete class with a required scalar may be known to `has()` but fail in `get()`. `has()` does not validate construction. An unknown ID returns false and `get()` throws a PSR-11 not-found exception.

Missing/uninferable graphs differ from broken executable code. If an integration intentionally tolerates a known failure, catch that specific exception in its explicit factory and return the intended fallback. Avoid blanket catches around autowiring and preserve cycle diagnostics.

The native constructor resolver detects constructor cycles. Arbitrary provider factories and aliases need an acyclic graph too; their recursion is not covered by that native guard in v1.9.0 ([issue #26](https://github.com/php-fast-forward/container/issues/26)). In 1.9.1, the provider guard reports those cycles as container errors and preserves ordered same-ID fallback to different providers. It isolates active paths per execution context; it does not synchronize concurrent service construction or control recursion inside an external container.

Run `../examples/autowiring.php` with a consumer autoloader to check standalone/delegated graphs, optional absence, constructor errors and cycles. No external services or credentials are needed.

Authorities: [v1.9.1 release](https://github.com/php-fast-forward/container/releases/tag/v1.9.1), [v1.9.1 source](https://github.com/php-fast-forward/container/tree/v1.9.1/src) at `9559b1e0fd2547bf8e04ea875c923d6710449eff`, [older v1.9.0 source](https://github.com/php-fast-forward/container/tree/v1.9.0/src), [autowiring guide](https://github.com/php-fast-forward/container/blob/v1.9.1/docs/advanced/autowire.rst), [PSR-11](https://www.php-fig.org/psr/psr-11/). The bundled workflow supplies the ordinary route; these links support confirmation and version-specific investigation.
