---
name: fast-forward-container
description: Integrate or maintain PSR-11 dependency injection in PHP projects using fast-forward/container. Use for configuration-backed registration, providers, factories, aliases, test overrides, existing-container composition, or native constructor autowiring. Inspect the installed Composer version before selecting APIs.
license: MIT
compatibility: PHP 8.3+ and Composer. Examples target fast-forward/container 1.9.0; inspect installed APIs for other versions. No agent-specific tools or setup scripts required.
metadata:
  author: php-fast-forward
  api-baseline: "1.9.0"
---

# Fast Forward Container

Implement the application's composition root, preserving its existing configuration and source order. Return concrete bootstrap/config/provider changes and focused behavior checks. Use this skill when the project uses `fast-forward/container` or the user selected it for PSR-11 integration. Generic PHP work, runtime library development and skill installation have different scopes; do not replace a framework container without a request to integrate it.

## Inspect before choosing an API

1. Read project instructions, `composer.json`, `composer.lock`, bootstrap and relevant providers/configuration. Inspect config files as code before executing them; PHP config may execute arbitrary code. Do not print credentials or unrelated values.
2. Confirm installed versions with `composer show fast-forward/container` and `composer show fast-forward/config`. If dependencies are absent, the lockfile establishes the intended version; install only within task authority. Do not update dependencies just to match this skill.
3. Examples target released **1.9.0**, with `fast-forward/config` 1.5.1. Its dependency ranges admit incompatible `psr/container` 1.0 and config versions before 1.1.4; check resolved versions and runtime mbstring support. An optional `AutowireContainer` delegate, native optional-before-default resolution and non-constructing `has()` are 1.9.0 behavior. For other versions inspect installed source/tests before applying version-sensitive rules. Load [versions and failures](references/versions-and-errors.md) for these compatibility checks or resolution errors.
4. Locate the composition root. Keep container lookups in bootstrap/factories; application services receive specific collaborators rather than a container.

## Choose an entry point

| Need | Entry point |
| --- | --- |
| Config/providers/existing-container composition | `FastForward\Container\container(...$sources)` |
| Same composition through a static method | `ContainerFactory::create(...$sources)` |
| Conditional or incremental registration | `(new ContainerBuilder())->add(...$sources)->build()` |
| Inferable concrete object graph only | `new AutowireContainer()` on 1.9.0+ |
| Autowiring around one existing PSR-11 container | `new AutowireContainer($delegate)` |

Helper, factory and builder accept `ConfigInterface`, PSR-11 containers, interop service providers, or their class names. Class-name initializers are constructed without arguments; pass objects when constructors need arguments. Raw arrays are not initializers: use `ArrayConfig` for configuration and `ArrayServiceProvider` for factories.

## Ordinary configuration-backed setup

Reuse the application's existing classes and Composer autoloader. Keep each class or interface in its own file with the matching name. For this example, Composer maps `App\\` to `src/`.

`src/GreetingInterface.php`:

```php
<?php

namespace App;

interface GreetingInterface
{
    public function greet(): string;
}
```

`src/Greeting.php`:

```php
<?php

namespace App;

final readonly class Greeting implements GreetingInterface
{
    public function __construct(private string $name) {}
    public function greet(): string { return 'Hello ' . $this->name; }
}
```

Create `bootstrap/container.php` after installing the Composer dependencies:

```php
<?php

declare(strict_types=1);

use App\Greeting;
use App\GreetingInterface;
use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerInterface as ContainerConfig;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use Psr\Container\ContainerInterface;
use function FastForward\Container\container;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = new ArrayConfig([
    'app' => ['name' => 'Storefront'],
    ContainerConfig::class => [
        new ArrayServiceProvider([
            GreetingInterface::class => static fn(ContainerInterface $c): GreetingInterface =>
                new Greeting($c->get('config.app.name')),
        ]),
    ],
]);

return container($config);
```

Verify from the project root:

```sh
php -r '$c = require "bootstrap/container.php"; if ($c->get(App\GreetingInterface::class)->greet() !== "Hello Storefront") { throw new RuntimeException("Unexpected greeting"); }'
```

The raw nested-provider key is **`FastForward\Container\ContainerInterface::class`**, not the PSR interface or a `config.*` key. Raw config reads `app.name`; the composed container exposes `config.app.name`. A plain provider closure receives the composed PSR-11 wrapper and can retrieve configuration or sibling services.

For file/environment config, scalar factories, class-string providers and nested-list errors, load [configuration scenarios](references/configuration.md). The complete checked fixture `examples/config.php` can run against any consumer autoloader:

```sh
php /path/to/fast-forward-container/examples/config.php ./vendor/autoload.php
```

It must print `configuration: ok`; mismatches throw regardless of PHP's `assert()` setting.

## Preserve resolution and lifetime rules

- Separate sources are tried in order: `container($testOverrides, $config, $defaults)`. The first successful source wins. Explicit entries precede native autowiring, including nested and optional dependencies. Later sources may recover from PSR-11 errors thrown by a source's `get()` after its `has()` succeeds. Errors from `has()`, including provider metadata failures, stop the lookup immediately.
- `AggregateServiceProvider($base, $overrides)` merges factories: **later duplicate factory keys win**. Duplicate extensions compose in provider order. This differs from first-source precedence.
- Interfaces and required scalars need explicit registration/factories. Bind the containing service for scalar arguments. `?Type $value` without a default remains required.
- Optional named objects try explicit entries and concrete autowiring before their defaults. Missing/uninferable graphs may use the default. Constructor/factory/type/autoload/default-expression failures and cycles propagate when no later source succeeds. Do not blanket-catch failures and silently substitute null.
- `has()` identifies known entries, not successful construction. Check `get()` and PSR-11 exceptions for construction health. External sources retain their own `has()` and internal resolution behavior.
- Successful resolutions are cached, including provider values such as null. Builder changes affect future builds only. Existing external containers, captured singleton objects and `ServiceFactory` values may remain shared across new compositions. Do not assume request/tenant isolation from a fresh wrapper.

Load [composition and factories](references/composition.md) for aliases, extensions, factory selection, external-container boundaries and test overrides. Run its `examples/composition.php` fixture when relevant. For standalone/delegated graphs and optional failure cases use `examples/autowiring.php` with [versions and failures](references/versions-and-errors.md).

## Finish with observable checks

Verify the requested service, interface identity, scalar config value, source conflict and repeated lookup. Include absence and runtime failure cases when touching optional behavior. Use synthetic fixtures and the existing project test runner. Bundled examples take an autoloader path; they never install dependencies or change application files.

Report the installed version, entry point, effective precedence, changed files and checks actually executed. A working PHP example and successful agent discovery are separate observations; a copied skill needs a supported discovery path and a refreshed agent session.
