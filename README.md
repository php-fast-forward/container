# FastForward\Container

A PSR-11 compliant aggregate container for PHP, designed to unify and resolve services across multiple container implementations. Built to work seamlessly with `php-di`, configuration objects, and custom container stacks.

## ✨ Features

- Aggregates multiple containers
- Caches resolved entries
- Integrates with `php-di`
- First-class support for configuration containers
- PSR-11 compliant

## 🚀 Installation

```bash
composer require fast-forward/container
```

## 🛠️ Usage

```php
use FastForward\Container\ContainerInterface;
use FastForward\Container\container;
use FastForward\Config\ArrayConfig;

$config = new ArrayConfig([
    ContainerInterface::class => [
        SomeServiceProvider::class, // strings will be initialized with a new call
        SomePsr11Container::class, // strings should be initializable with a simple new call
        new OtherServiceProvider('withArgument'), // objects that implement Interop\Container\ServiceProviderInterface
        new ServiceManager($dependencies), // objects that implement Psr\Container\ContainerInterface
    ],
]);

$container = container($config);

// or using the container function directly with a configuration variadic
// $container = container(
//     SomeServiceProvider::class,
//     SomePsr11Container::class,
//     new ApplicationConfig(), // that implements ConfigInterface with the ContainerInterface key
//     new OtherServiceProvider('withArgument'),
//     new ServiceManager($dependencies),
// );

// Retrieve service
$service = $container->get(SomeService::class);
```

## 📄 License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## 🔗 Links

- [Repository](https://github.com/php-fast-forward/container)
- [Packagist](https://packagist.org/packages/php-fast-forward/container)
- [RFC 2119](https://datatracker.ietf.org/doc/html/rfc2119)
- [PSR-11 Container Interface](https://www.php-fig.org/psr/psr-11/)
