# Composition, factories and lifetimes

Load this for multiple sources, test overrides, aliases, extensions or factory selection. Run `../examples/composition.php` against the consumer autoloader.

## First source versus merged factory

```php
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProvider\AggregateServiceProvider;
use function FastForward\Container\container;

$base = new ArrayServiceProvider(['label' => static fn(): string => 'base']);
$test = new ArrayServiceProvider(['label' => static fn(): string => 'test']);
$first = container($test, $base); // label: test; first successful source wins
$merged = container(new AggregateServiceProvider($base, $test)); // label: test; later factory wins
```

Separate sources first call `has()`. When it succeeds, a PSR-11 exception raised by that source's `get()` permits trying the next source. If none succeeds, the first construction error is preserved. An exception from `has()` itself, including a failure reading provider factories/extensions, propagates immediately; later sources are not tried. A successful fallback is cached; later lookups do not automatically retry the earlier source.

Provider merges preserve numeric string IDs and apply the same later-duplicate precedence as for other IDs.

## Select the factory by its argument contract

Factory classes are under `FastForward\Container\Factory`.

| Factory | Use | Important boundary |
| --- | --- | --- |
| Plain provider closure `fn($c) => ...` | Configured scalars, explicit lookups, application logic | Receives the wrapper; construct and return the service |
| `ServiceFactory($value)` | Fixed object/scalar/array/null | Returns the same value; does not invoke it |
| `AliasFactory($id)` | Existing service under another ID/interface | Calls wrapper `get($id)`; preserves object identity |
| `InvokableFactory(ClassName::class, ...$arguments)` | Constructor with supplied arguments | No implicit parameter inference; strings matching known IDs are dereferenced |
| `CallableFactory($callable)` | Callable with named object-typed parameters | Resolves by type; scalar/untyped/union/intersection parameters are unsuitable; native constructor default rules do not apply |
| `MethodFactory($target, 'method', ...$arguments)` | Public static/instance creation method | String arguments matching known IDs are resolved; instance targets are retrieved by ID |

Prefer a plain closure when literal strings could collide with IDs or scalar/default handling is needed. `CallableFactory` is not a wrapper for a callback expecting an untyped `$container`.

Service-ID/interface instance targets resolve before reflecting the actual object's method. Registered target failures, including nested not-found errors, propagate; direct `new ClassName()` fallback is allowed only when `has(ClassName::class)` is false. Concrete public static methods are invoked without constructing an instance; abstract static contracts resolve their registered implementation. With `AutowireContainer`, a concrete class can be known even when its constructor fails, so that failure propagates rather than taking the absent-entry fallback.

## Extensions and aliases

`ArrayServiceProvider($factories, $extensions)` accepts extension callbacks keyed by service ID. Each receives the wrapper and previous value; explicitly return the intended next value. A void return declaration preserves the previous value for compatibility; returning null replaces it with null. Duplicate extensions in merged providers compose in registration order.

Class-keyed extensions may apply to objects obtained through other IDs. An extension with no factory can create an entry and receives null initially. Provider containers may infer a concrete-class alias when no explicit factory, extension or cached entry already owns that class ID.

Each provider adapter tracks completed class-extension results by object identity. Distinct instances receive their own class extension; aliases/external lookups returning the same instance reuse its completed result, including object replacements, scalars or null. An alias-specific extension still executes and receives a completed replacement when one is already recorded. An ID extension runs first, then at most one applicable concrete-class extension for that resolution; returning a different class does not request unlimited recursive decoration. This tracking belongs to each adapter, so do not assume that passing the same object through independent adapters makes all decoration global.

Provider factories, aliases and extensions receive circular-resolution diagnostics. Inspect the exception's previous-cause chain for the circular path: provider boundaries may wrap it as an invalid-service error. Paths follow the current Fiber, or the wrapper in the main context; failed paths are cleaned up so later retries can succeed. Independent Fibers resolving the same ID do not share an active path. This isolates cycle detection, not concurrent construction: there is no single-flight lock guaranteeing one construction across suspended Fibers. Keep the application's service graph acyclic and use a factory service when deliberate repeated creation is needed.

Run `../examples/provider-resolution.php` against the consumer autoloader. It checks numeric IDs, an instance-method service target and its failure, replacement reuse through aliases, provider cycles/retry and independent Fibers.

## External containers and lifetime boundaries

`new AutowireContainer($existingPsr11)` gives the delegate priority for direct, nested and optional lookups. Omission or null uses an empty delegate.

Providers adapted by `container($existing, $provider, $config)` receive the composed wrapper and can see sibling sources. An already constructed external container keeps its own internal factory/dependency-resolution rules; wrapping it does not connect those factories to later sibling sources. Use a provider adapted by the builder when cross-source construction is needed.

An independently built autowire-enabled container placed before later sources may construct a class before those sources are tried. Prefer raw providers/config/ordinary containers in one composition root.

Native and provider adapters cache successful resolutions, including provider null values. Fresh builds create fresh adapters, but reused external containers, prebuilt objects and captured singleton closures can share objects across compositions. For tests, put overrides first and create fresh relevant sources. Design request/user/tenant lifetimes explicitly instead of assuming a fresh wrapper clears external state.
