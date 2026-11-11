# Versions and failures

This skill supports `fast-forward/container` **`^1.10`**, PHP 8.3+ and Composer 2. Earlier library versions are outside its supported API contract. Compare `composer.lock`, `composer show` and installed source; a constraint alone does not establish the installed version. Report a mismatch before applying version-dependent guidance. Preserve the project's dependency policy; copying the skill does not upgrade dependencies.

## Dependencies and resolution contracts

Check `composer show fast-forward/config` and `composer show psr/container`. The library requires Config `^1.6` and PSR Container `^1.1` or `^2.0`. Verify the actual runtime's platform requirements and mbstring functions or a compatible polyfill.

| Area | Behavior |
| --- | --- |
| Aggregated numeric IDs | Preserve opaque IDs, matching extensions and later-provider overrides |
| Method factories | Resolve instance service IDs/interfaces; reflect the actual object; preserve registered-target failures |
| Class extensions | Apply independently to distinct instances and reuse completed results for the same identity |
| Provider recursion | Detect factory/alias/extension cycles, clean up retries and isolate active Fiber paths |

See [composition and factories](composition.md) for the complete contracts and run the bundled examples against the consumer autoloader.

PHP-DI DSL, attributes, proxies and compilation are outside this library's native engine. Use explicit provider factories when construction needs application-specific rules; direct PHP-DI consumers must declare their dependency.

## Native constructor outcomes

| Dependency/parameter | Outcome |
| --- | --- |
| Named object with explicit entry | Try explicit entries before native autowiring |
| Constructible concrete named object | Recursively build and cache on success |
| Optional object absent or graph structurally uninferable | Use declared default, including non-null defaults |
| Constructor/factory/autoloader/type/default-expression failure or cycle from a source's `get()` | Selected provider errors propagate immediately; independent external PSR-11 sources may try later sources |
| Source `has()` failure, including unreadable provider metadata | Propagate immediately without trying later sources |
| Nullable parameter without default | Required; nullable does not mean omittable |
| Required scalar, ambiguous union/intersection/DNF | Explicit factory for the containing service |
| Variadic collection | No automatic element discovery; supply elements explicitly |
| Optional internal argument without reflected default | Omit it when reflection permits omission |

Interfaces are not automatically mapped to arbitrary implementations. A concrete class with a required scalar may be known to `has()` but fail in `get()`. `has()` does not validate construction. An unknown ID returns false and `get()` throws a PSR-11 not-found exception.

Missing/uninferable graphs differ from broken executable code. If an integration intentionally tolerates a known failure, catch that specific exception in its explicit factory and return the intended fallback. Avoid blanket catches around autowiring and preserve cycle diagnostics.

The native constructor resolver detects constructor cycles. The provider guard reports factory, alias and extension cycles as container errors. Default composition selects the first callable factory, so same-ID re-entry remains a cycle rather than selecting a later provider. Independent external PSR-11 containers retain their own aggregate fallback semantics. It isolates active paths per execution context; it does not synchronize concurrent service construction or control recursion inside an external container.

Run `../examples/autowiring.php` with a consumer autoloader to check standalone/delegated graphs, optional absence, constructor errors and cycles. No external services or credentials are needed.

For API confirmation, inspect the installed library's `src/` and consult the [library guides](https://php-fast-forward.github.io/container/) and [PSR-11](https://www.php-fig.org/psr/psr-11/). The bundled examples provide executable checks for the supported contract.
