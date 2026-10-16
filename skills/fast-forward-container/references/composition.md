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

Separate sources try the next source after failure. If none succeeds, the first construction error is preserved. A successful fallback is cached; later lookups do not automatically retry the earlier source.

## Select the factory by its argument contract

Factory classes are under `FastForward\Container\Factory`.

| Factory | Use | Important boundary |
| --- | --- | --- |
| Plain provider closure `fn($c) => ...` | Configured scalars, explicit lookups, application logic | Receives the wrapper; construct and return the service |
| `ServiceFactory($value)` | Fixed object/scalar/array/null | Returns the same value; does not invoke it |
| `AliasFactory($id)` | Existing service under another ID/interface | Calls wrapper `get($id)`; preserves object identity |
| `InvokableFactory(ClassName::class, ...$arguments)` | Constructor with supplied arguments | No implicit parameter inference; strings matching known IDs are dereferenced |
| `CallableFactory($callable)` | Callable with named object-typed parameters | Resolves by type; scalar/untyped/union/intersection parameters are unsuitable; native constructor default rules do not apply |
| `MethodFactory(ClassName::class, 'method', ...$arguments)` | Public static/instance creation method | Strings may be IDs; failed instance lookup can fall back to `new ClassName()` |

Prefer a plain closure when literal strings could collide with IDs, method fallback would obscure errors, or scalar/default handling is needed. `CallableFactory` is not a wrapper for a callback expecting an untyped `$container`.

For v1.9.0, pass a reflectable class name to `MethodFactory`, not an arbitrary service alias: its implementation reflects the name before looking up the instance ([issue #23](https://github.com/php-fast-forward/container/issues/23)).

## Extensions and aliases

`ArrayServiceProvider($factories, $extensions)` accepts extension callbacks keyed by service ID. Each receives the wrapper and previous value; explicitly return the intended next value. A void return declaration preserves the previous value for compatibility; returning null replaces it with null. Duplicate extensions in merged providers compose in registration order.

Class-keyed extensions may apply to objects obtained through other IDs. Avoid accidentally decorating twice through aliases. An extension with no factory can create an entry and receives null initially. Provider containers may cache a resolved object under its concrete class ID as well.

For distinct objects registered under several IDs, prefer ID-specific extensions on v1.9.0: class-keyed extension application can depend on whether the concrete class entry was already cached ([issue #22](https://github.com/php-fast-forward/container/issues/22)).

## External containers and lifetime boundaries

`new AutowireContainer($existingPsr11)` gives the delegate priority for direct, nested and optional lookups. Omission or null uses an empty delegate on 1.9.0+.

Providers adapted by `container($existing, $provider, $config)` receive the composed wrapper and can see sibling sources. An already constructed external container keeps its own internal factory/dependency-resolution rules; wrapping it does not connect those factories to later sibling sources. Use a provider adapted by the builder when cross-source construction is needed.

An independently built autowire-enabled container placed before later sources may construct a class before those sources are tried. Prefer raw providers/config/ordinary containers in one composition root.

Native and provider adapters cache successful resolutions, including provider null values. Fresh builds create fresh adapters, but reused external containers, prebuilt objects and captured singleton closures can share objects across compositions. For tests, put overrides first and create fresh relevant sources. Design request/user/tenant lifetimes explicitly instead of assuming a fresh wrapper clears external state.
