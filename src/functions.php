<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/container.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @link      https://github.com/php-fast-forward/container
 * @copyright Copyright (c) 2025 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\Container;

use FastForward\Config\ConfigInterface;
use FastForward\Config\Container\ConfigContainer;
use FastForward\Container\Exception\InvalidArgumentException;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Creates and assembles a fully composed container instance.
 *
 * This factory function is responsible for composing a container from a list of initializers,
 * which MAY include:
 * - PSR-11 container instances
 * - Service providers implementing ServiceProviderInterface
 * - Configuration instances implementing ConfigInterface
 * - Class names as strings (which MUST be instantiable)
 *
 * If a ConfigContainer is included, it SHALL attempt to resolve additional nested container
 * definitions from its configuration using the key `${alias}.${ContainerInterface::class}`.
 * Any invalid initializers MUST result in an InvalidArgumentException being thrown.
 *
 * The final container returned is an AutowireContainer that wraps an
 * AggregateContainer composed of all resolved sources.
 *
 * @param ConfigInterface|PsrContainerInterface|ServiceProviderInterface|string ...$initializers
 *                                                                                               A variadic list of container initializers, optionally including config or provider classes.
 *
 * @return ContainerInterface the composed and autowire-enabled container
 *
 * @throws InvalidArgumentException if an unsupported initializer type is encountered
 */
function container(
    ConfigInterface|PsrContainerInterface|ServiceProviderInterface|string ...$initializers,
): ContainerInterface {
    $aggregateContainer = new AggregateContainer();

    $getContainer = static fn ($initializer) => match (true) {
        $initializer instanceof PsrContainerInterface    => $initializer,
        $initializer instanceof ServiceProviderInterface => new ServiceProviderContainer($initializer, $aggregateContainer),
        $initializer instanceof ConfigInterface          => new ConfigContainer($initializer),
        default                                          => null,
    };

    $resolve = static fn ($initializer) => match (true) {
        \is_object($initializer)   => $getContainer($initializer),
        class_exists($initializer) => $getContainer(new ($initializer)()),
        default                    => throw InvalidArgumentException::forUnsupportedInitializer($initializer),
    };

    $configKey  = \sprintf('%s.%s', ConfigContainer::ALIAS, ContainerInterface::class);

    foreach ($initializers as $initializer) {
        $container = $resolve($initializer);
        $aggregateContainer->append($container);

        if ($container instanceof ConfigContainer) {
            try {
                foreach ($container->get($configKey) as $nested) {
                    $aggregateContainer->append($resolve($nested));
                }
            } catch (\Throwable) {
                // Ignored
            }
        }
    }

    return new AutowireContainer($aggregateContainer);
}
