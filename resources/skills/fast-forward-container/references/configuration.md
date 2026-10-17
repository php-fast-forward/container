# Configuration scenarios

Load this when configuration drives registration. Run `../examples/config.php` for checked contracts.

## Raw keys and exposed IDs

```php
use FastForward\Config\ArrayConfig;
use FastForward\Config\ConfigInterface;
use FastForward\Container\ContainerInterface as ContainerConfig;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use function FastForward\Container\container;

$config = new ArrayConfig([
    'app' => ['name' => 'Storefront'],
    ContainerConfig::class => [new ArrayServiceProvider([
        'app.name' => static fn($c): string => $c->get('config.app.name'),
    ])],
]);
$c = container($config);
// $config->get('app.name') === 'Storefront'
// $c->get('config.app.name') === 'Storefront'
// $c->get(ConfigInterface::class) === $config
// $c->get('app.name') exists because the provider explicitly registered that ID.
```

Do not prefix raw keys with `config.` or use the PSR interface as the provider-list key. Associative `ArrayConfig` subtrees return config objects; use leaf IDs or `toArray()` when a factory needs an array.

## File/environment configuration and scalar construction

Preserve the application's existing config loader, file order, environment selection and secret storage. Pass its resulting `ConfigInterface` to `container($config)`. A small application may use a trusted PHP file returning an array and wrap `new ArrayConfig(require $path)`; inspect unknown PHP before requiring it.

Keep values in ordinary keys and nested initializers under `ContainerConfig::class`. Resolve required scalars explicitly in a factory for the containing service:

```php
$provider = new ArrayServiceProvider([
    Client::class => static fn($c): Client => new Client(
        $c->get('config.client.endpoint'),
        $c->get('config.client.timeout'),
    ),
]);
```

`Client` is the application's class with those arguments. A plain provider closure receives the wrapper. `CallableFactory` instead resolves the callable's typed parameters individually.

Nested class-string providers need no-argument constructors; pass objects when arguments are required. Nested sources follow their config immediately: `container($testOverrides, $config, $fallbackProvider)` tries overrides, config values, that config's nested sources, fallback provider, then native autowiring. A config that also implements PSR-11 is adapted as an existing container first and does not receive normal config expansion.

## Optional list expansion is different from service resolution

The builder catches errors reading/adapting the optional nested-initializer list. Expansion stops for that config; already appended sources remain and later explicit initializers still run. A malformed entry can hide subsequent nested entries. Validate the list and resolve representative services. Pass essential initializers explicitly when adaptation errors must fail visibly.

This composition-time policy does not suppress constructor/factory failures while resolving optional object dependencies.

## Helper, factory or builder

`container($config, $provider)` and `ContainerFactory::create($config, $provider)` have the same composition behavior. For a feature flag:

```php
$builder = (new \FastForward\Container\ContainerBuilder())->add($config);
if ($featureEnabled) {
    $builder->add($featureProvider);
}
$c = $builder->build();
```

Registration order is precedence, not a mutable map. Register test overrides before defaults. A new build sees later registrations; previously built containers keep their composition. Existing source objects are reused.
