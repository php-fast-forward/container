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
 * Registers ordered initializers and builds independent container compositions.
 *
 * Registration SHALL retain source objects and class names without resolving services.
 * Each build() MUST create a fresh aggregate, provider/config adapters, and autowiring
 * container. Supplied PSR-11 containers SHALL be reused without cloning their state.
 *
 * Later registrations SHALL affect subsequent builds only. Provider adapters SHALL
 * receive their build's aggregate so dependencies can resolve across all sources.
 */
final class ContainerBuilder
{
    /**
     * @var list<ConfigInterface|PsrContainerInterface|ServiceProviderInterface|string> the ordered sources retained for subsequent builds
     */
    private array $initializers = [];

    /**
     * Registers one or more initializers for subsequent builds.
     *
     * This method SHALL append sources in their supplied order and return this builder.
     * Class names SHALL be instantiated and adapted when build() is called.
     * Previously built containers MUST NOT be changed by later registrations.
     *
     * @param ConfigInterface|PsrContainerInterface|ServiceProviderInterface|string ...$initializers the configuration objects, containers, providers, or initializer class names
     *
     * @return self this builder for incremental, fluent registration
     */
    public function add(
        ConfigInterface|PsrContainerInterface|ServiceProviderInterface|string ...$initializers,
    ): self {
        foreach ($initializers as $initializer) {
            $this->initializers[] = $initializer;
        }

        return $this;
    }

    /**
     * Creates a fresh container stack from the registered initializers.
     *
     * This method SHALL adapt sources in registration order, append optional configured
     * sources, and return an AutowireContainer wrapping the completed aggregate.
     * Class names MUST be instantiable without required constructor arguments.
     * The builder SHALL retain registrations without retaining assembled container state.
     *
     * @return ContainerInterface the composed and autowire-enabled container
     *
     * @throws InvalidArgumentException if an initializer class cannot be found or adapted
     */
    public function build(): ContainerInterface
    {
        $aggregateContainer = new AggregateContainer();

        foreach ($this->initializers as $initializer) {
            $container = $this->resolveInitializer($initializer, $aggregateContainer);
            $aggregateContainer->append($container);

            if ($container instanceof ConfigContainer) {
                $this->appendConfiguredContainers($container, $aggregateContainer);
            }
        }

        return new AutowireContainer($aggregateContainer);
    }

    /**
     * Resolves an initializer object or constructs its class before adaptation.
     *
     * Existing objects SHALL be passed directly to adapter selection. Class names SHALL
     * be instantiated with an argument-free constructor for each build.
     *
     * @param mixed $initializer an initializer object or instantiable class name
     * @param AggregateContainer $aggregateContainer the shared resolution wrapper
     *
     * @return PsrContainerInterface the adapted initializer
     *
     * @throws InvalidArgumentException if an initializer class cannot be found or adapted
     */
    private function resolveInitializer(
        mixed $initializer,
        AggregateContainer $aggregateContainer
    ): PsrContainerInterface {
        return match (true) {
            \is_object($initializer) => $this->adaptInitializer($initializer, $aggregateContainer),
            class_exists($initializer) => $this->adaptInitializer(new ($initializer)(), $aggregateContainer),
            default => throw InvalidArgumentException::forUnsupportedInitializer($initializer),
        };
    }

    /**
     * Selects a container implementation for a constructed initializer.
     *
     * PSR-11 containers SHALL take precedence over provider and configuration adapters.
     * Unsupported objects MUST cause an InvalidArgumentException before reaching the aggregate.
     *
     * @param object $initializer the constructed initializer
     * @param AggregateContainer $aggregateContainer the shared resolution wrapper
     *
     * @return PsrContainerInterface the container or provider/config adapter
     *
     * @throws InvalidArgumentException if the initializer object cannot be adapted
     */
    private function adaptInitializer(
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
     * Entries SHALL be adapted in order using the existing container configuration key.
     * Failures while reading or adapting this optional configuration SHALL stop that
     * expansion and be ignored, preserving previously appended and later explicit sources.
     *
     * @param ConfigContainer $configContainer the configuration entry source
     * @param AggregateContainer $aggregateContainer the shared resolution wrapper
     *
     * @return void
     */
    private function appendConfiguredContainers(
        ConfigContainer $configContainer,
        AggregateContainer $aggregateContainer
    ): void {
        $configKey = \sprintf('%s.%s', ConfigContainer::ALIAS, ContainerInterface::class);

        try {
            foreach ($configContainer->get($configKey) as $nested) {
                $aggregateContainer->append($this->resolveInitializer($nested, $aggregateContainer));
            }
        } catch (Throwable) {
            // Preserve the existing helper's optional configuration behavior.
        }
    }
}
