<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/container.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2025-2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/container
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\Container\Tests\Integration;

use RuntimeException;
use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerBuilder;
use FastForward\Container\ContainerFactory;
use FastForward\Container\ContainerInterface;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use Psr\Container\ContainerInterface as PsrContainerInterface;

use function FastForward\Container\container;

require $argv[1];

/**
 * The explicitly configured dependency in the production consumer.
 */
interface ConsumerDependency
{
    /**
     * @return string the configured application name
     */
    public function name(): string;
}

/**
 * Keeps scalar configuration behind an explicit provider factory.
 */
final readonly class ConfiguredDependency implements ConsumerDependency
{
    /**
     * @param string $applicationName the value read through config.*
     */
    public function __construct(
        private string $applicationName
    ) {}

    /**
     * @return string the configured application name
     */
    public function name(): string
    {
        return $this->applicationName;
    }
}

/**
 * An optional concrete dependency resolved through native autowiring.
 */
final class OptionalDependency {}

/**
 * Combines explicit configuration and native constructor resolution.
 */
final readonly class ConsumerService
{
    /**
     * @param ConsumerDependency $dependency the provider-defined dependency
     * @param OptionalDependency|null $optional the autowired optional dependency
     */
    public function __construct(
        public ConsumerDependency $dependency,
        public ?OptionalDependency $optional = null,
    ) {}
}

$config = new ArrayConfig([
    'app' => [
        'name' => 'production-consumer',
    ],
    ContainerInterface::class => [
        new ArrayServiceProvider([
            ConsumerDependency::class => static fn(PsrContainerInterface $container): ConsumerDependency => new ConfiguredDependency(
                $container->get('config.app.name'),
            ),
            'nullable' => static fn(): mixed => null,
        ]),
    ],
]);

$containers = [
    ContainerFactory::create($config),
    (new ContainerBuilder())->add($config)->build(),
    container($config),
];

foreach ($containers as $container) {
    $service = $container->get(ConsumerService::class);

    if (! $service instanceof ConsumerService
        || 'production-consumer' !== $service->dependency->name()
        || ! $service->optional instanceof OptionalDependency
        || $service !== $container->get(ConsumerService::class)
        || $service->dependency !== $container->get(ConsumerDependency::class)
        || ! $container->has('nullable')
        || null !== $container->get('nullable')
    ) {
        throw new RuntimeException('The production consumer contract failed.');
    }
}

if ($containers[0]->get(ConsumerService::class) === $containers[1]->get(ConsumerService::class)) {
    throw new RuntimeException('Separate compositions must retain independent service caches.');
}

echo "Production consumer passed.\n";
