<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Configuration;

use FastForward\Config\ArrayConfig;
use FastForward\Config\ConfigInterface;
use FastForward\Container\ContainerBuilder;
use FastForward\Container\ContainerFactory;
use FastForward\Container\ContainerInterface as ContainerConfig;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function FastForward\Container\container;

require $argv[1] ?? getcwd() . '/vendor/autoload.php';

/**
 * A configured application dependency.
 */
interface GreetingInterface
{
    /**
     * @return string the configured greeting
     */
    public function greet(): string;
}

/**
 * A service with a required scalar that needs an explicit factory.
 */
final readonly class Greeting implements GreetingInterface
{
    /**
     * @param string $name the application name
     */
    public function __construct(
        private string $name
    ) {}

    /**
     * @return string the configured greeting
     */
    public function greet(): string
    {
        return 'Hello ' . $this->name;
    }
}

$config = new ArrayConfig([
    'app' => [
        'name' => 'Storefront',
    ],
    ContainerConfig::class => [
        new ArrayServiceProvider([
            GreetingInterface::class => static fn(ContainerInterface $c): GreetingInterface => new Greeting($c->get(
                'config.app.name'
            )),
        ]),
    ],
]);

foreach ([
    container($config),
    ContainerFactory::create($config),
    (new ContainerBuilder())->add($config)->build(),
] as $c) {
    $service = $c->get(GreetingInterface::class);
    if ('Storefront' !== $config->get('app.name')
        || 'Storefront' !== $c->get('config.app.name')
        || $c->get(ConfigInterface::class) !== $config
        || $c->has('app.name')
        || ! $service instanceof GreetingInterface
        || 'Hello Storefront' !== $service->greet()
        || $service !== $c->get(GreetingInterface::class)
    ) {
        throw new RuntimeException('Configuration, scalar factory, interface binding or caching failed.');
    }
}

$overrides = new ArrayServiceProvider([
    GreetingInterface::class => static fn(): GreetingInterface => new Greeting('Test'),
]);
if ('Hello Test' !== container($overrides, $config)->get(GreetingInterface::class)->greet()) {
    throw new RuntimeException('The earlier test override did not win over the nested config provider.');
}

echo 'configuration: ok', \PHP_EOL;
