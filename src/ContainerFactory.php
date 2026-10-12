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

namespace FastForward\Container;

use Throwable;
use FastForward\Config\ConfigInterface;
use FastForward\Config\Container\ConfigContainer;
use FastForward\Container\Exception\InvalidArgumentException;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Composes a fresh aggregate and autowiring container from explicit initializers.
 *
 * This factory SHALL compose PSR-11 containers, service providers, configuration
 * objects, and initializer class names in their supplied order.
 *
 * Each call to create() MUST create a fresh aggregate, provider adapters, and
 * autowiring container. The factory SHALL NOT retain assembled container state.
 * Supplied PSR-11 containers SHALL be used directly without cloning their state.
 *
 * Provider adapters SHALL receive the aggregate as their wrapper so dependencies
 * can be resolved across all sources in the composed stack.
 */
final class ContainerFactory
{
    /**
     * Creates and assembles a fresh container stack.
     *
     * This method SHALL adapt each initializer, append optional configured sources,
     * and return an AutowireContainer wrapping the completed aggregate.
     * Initializer class names MUST be instantiable without required constructor arguments.
     *
     * @param ConfigInterface|PsrContainerInterface|ServiceProviderInterface|string ...$initializers the ordered configuration objects, containers, providers, or initializer class names
     *
     * @return ContainerInterface the composed and autowire-enabled container
     *
     * @throws InvalidArgumentException if an initializer class cannot be found or adapted
     */
    public static function create(
        ConfigInterface|PsrContainerInterface|ServiceProviderInterface|string ...$initializers,
    ): ContainerInterface {
        $aggregateContainer = new AggregateContainer();

        foreach ($initializers as $initializer) {
            $container = self::resolveInitializer($initializer, $aggregateContainer);
            $aggregateContainer->append($container);

            if ($container instanceof ConfigContainer) {
                self::appendConfiguredContainers($container, $aggregateContainer);
            }
        }

        return new AutowireContainer($aggregateContainer);
    }

    /**
     * Resolves an initializer object or constructs its class before adaptation.
     *
     * Existing objects SHALL be passed directly to the adapter selection. Class names
     * SHALL be instantiated with an argument-free constructor before being adapted.
     *
     * @param mixed $initializer an initializer object or instantiable class name
     * @param AggregateContainer $aggregateContainer the shared resolution wrapper
     *
     * @return PsrContainerInterface the adapted initializer
     *
     * @throws InvalidArgumentException if an initializer class cannot be found or adapted
     */
    private static function resolveInitializer(
        mixed $initializer,
        AggregateContainer $aggregateContainer
    ): PsrContainerInterface {
        return match (true) {
            \is_object($initializer) => self::adaptInitializer($initializer, $aggregateContainer),
            class_exists($initializer) => self::adaptInitializer(new ($initializer)(), $aggregateContainer),
            default => throw InvalidArgumentException::forUnsupportedInitializer($initializer),
        };
    }

    /**
     * Selects a container implementation for a constructed initializer.
     *
     * PSR-11 containers SHALL take precedence over service-provider and configuration
     * adapters. Unsupported objects MUST cause an InvalidArgumentException before
     * an invalid container can reach the aggregate.
     *
     * @param object $initializer the constructed initializer
     * @param AggregateContainer $aggregateContainer the shared resolution wrapper
     *
     * @return PsrContainerInterface the container or provider/config adapter
     *
     * @throws InvalidArgumentException if the initializer object cannot be adapted
     */
    private static function adaptInitializer(
        object $initializer,
        AggregateContainer $aggregateContainer
    ): PsrContainerInterface {
        return match (true) {
            $initializer instanceof PsrContainerInterface => $initializer,
            $initializer instanceof ServiceProviderInterface => new ServiceProviderContainer(
                $initializer,
                $aggregateContainer,
            ),
            $initializer instanceof ConfigInterface => new ConfigContainer($initializer),
            default => throw InvalidArgumentException::forUnsupportedInitializer($initializer),
        };
    }

    /**
     * Appends additional initializers declared in a configuration container.
     *
     * The method SHALL read the existing container configuration key and adapt its
     * entries in order. Failures while reading or adapting this optional configuration
     * SHALL stop that configuration expansion and be ignored, preserving the helper's policy.
     *
     * @param ConfigContainer $configContainer the configuration entry source
     * @param AggregateContainer $aggregateContainer the shared resolution wrapper
     *
     * @return void
     */
    private static function appendConfiguredContainers(
        ConfigContainer $configContainer,
        AggregateContainer $aggregateContainer
    ): void {
        $configKey = \sprintf('%s.%s', ConfigContainer::ALIAS, ContainerInterface::class);

        try {
            foreach ($configContainer->get($configKey) as $nested) {
                $aggregateContainer->append(self::resolveInitializer($nested, $aggregateContainer));
            }
        } catch (Throwable) {
            // Preserve the existing helper's optional configuration behavior.
        }
    }
}
