# Examples

The examples are ordered from the most common onboarding flow to the more specialized container features.

- `php examples/01-basic-services.php`: register a simple provider, resolve small value objects, and fetch a concrete service.

- `php examples/02-autowiring.php`: register only the dependency that needs configuration and let autowiring resolve the rest of the object graph.

- `php examples/03-multiple-providers.php`: compose feature-focused providers with `container($providerA, $providerB)`.

- `php examples/04-config-driven-container.php`: load providers from `ArrayConfig` and consume configuration through `config.*` entries.

- `php examples/05-service-extensions.php`: decorate a resolved service with provider extensions.

- `php examples/06-factory-helpers.php`: use `ServiceFactory`, `InvokableFactory`, `AliasFactory`, `CallableFactory`, and `MethodFactory` together.

Auxiliary files:

- `examples/bootstrap.php`: loads the Composer autoloader and provides small CLI helper functions shared by the examples.
