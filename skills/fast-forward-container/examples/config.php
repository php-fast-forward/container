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

require __DIR__ . '/Fixtures/Configuration/GreetingInterface.php';
require __DIR__ . '/Fixtures/Configuration/Greeting.php';

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
