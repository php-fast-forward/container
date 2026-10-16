# Versions and failures

The API baseline is released `fast-forward/container` **v1.9.0**, PHP 8.3+. The skill is bundled after that release; v1.9.0 itself does not contain this directory. Compare `composer.lock`, `composer show` and installed source. A constraint alone does not establish the installed version. Preserve the project's dependency policy; do not silently upgrade it.

Also inspect `composer show psr/container`. v1.9.0's declared range allows PSR container 1.0.0, but that interface's untyped signatures conflict with the library's typed methods. PSR container 1.1.x or 2.x avoids that startup failure; report incompatible constraints and resolve them deliberately rather than silently updating the consumer. See [issue #20](https://github.com/php-fast-forward/container/issues/20).

For configuration-backed composition, inspect `composer show fast-forward/config` too. Versions before 1.1.4 have an older `ConfigContainer` prefix contract and do not implement the documented `config.*`/nested-provider route correctly ([issue #27](https://github.com/php-fast-forward/container/issues/27)). The bundled examples were exercised with config 1.5.1. Its implementation uses mbstring functions; verify that the actual PHP runtime provides the extension or a compatible loaded polyfill rather than relying only on Composer's declared requirements.

Before 1.9.0, the internal engine used PHP-DI, an `AutowireContainer` delegate was required and `has()` eagerly attempted construction. Do not promise native optional-before-default behavior there. Explicit provider factories are the version-inspectable alternative. PHP-DI DSL, attributes, proxies and compilation are outside this library's native engine; direct PHP-DI consumers must declare their dependency.

## Native constructor outcomes in 1.9.0

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

The native constructor resolver detects constructor cycles. Arbitrary provider factories and aliases need an acyclic graph too; their recursion is not covered by that native guard in v1.9.0 ([issue #26](https://github.com/php-fast-forward/container/issues/26)).

Run `../examples/autowiring.php` with a consumer autoloader to check standalone/delegated graphs, optional absence, constructor errors and cycles. No external services or credentials are needed.

Baseline authorities: [v1.9.0 source](https://github.com/php-fast-forward/container/tree/v1.9.0/src), [autowiring guide](https://github.com/php-fast-forward/container/blob/v1.9.0/docs/advanced/autowire.rst), [PSR-11](https://www.php-fig.org/psr/psr-11/). The bundled workflow supplies the ordinary route; these links support confirmation and version-specific investigation.
