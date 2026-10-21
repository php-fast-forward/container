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
use FastForward\Container\ServiceProvider\FirstFactoryServiceProvider;
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
     * Builds the default two-pass provider composition without executing service callbacks.
     *
     * The first callable factory wins. Every provider extension runs in registration
     * order after factory selection. Ordered provider views retain source positions;
     * external PSR-11/config sources remain independent. Selected failures propagate.
     * Class names and adapters are recreated for each build.
     *
     * @return ContainerInterface a fresh stack with one required provider source
     */
    public function build(): ContainerInterface
    {
        $sources = $this->collectCompositionSources();
        $providers = array_values(array_filter(
            $sources,
            static fn(object $source): bool => ! $source instanceof PsrContainerInterface,
        ));
        $aggregate = new AggregateContainer();
        $provider = new FirstFactoryServiceProvider(...$providers);
        $providerContainer = new ServiceProviderContainer($provider, $aggregate);
        $providerIndex = 0;

        foreach ($sources as $source) {
            if ($source instanceof PsrContainerInterface) {
                $aggregate->append($source);

                continue;
            }

            $aggregate->appendRequired(new ProviderSourceContainer($providerContainer, $provider, $providerIndex));
            ++$providerIndex;
        }

        return new AutowireContainer($aggregate);
    }

    /**
     * Expands one level of optional configured initializers in registration order.
     *
     * Providers remain definitions until the complete ordered list has been gathered.
     * Invalid optional entries stop expansion, retaining prior and later explicit sources.
     *
     * @return list<PsrContainerInterface|ServiceProviderInterface> ordered normalized sources
     */
    private function collectCompositionSources(): array
    {
        $sources = [];
        $configKey = \sprintf('%s.%s', ConfigContainer::ALIAS, ContainerInterface::class);

        foreach ($this->initializers as $initializer) {
            $source = $this->resolveCompositionSource($initializer);
            $sources[] = $source;

            if (! $source instanceof ConfigContainer) {
                continue;
            }

            try {
                foreach ($source->get($configKey) as $nested) {
                    $sources[] = $this->resolveCompositionSource($nested);
                }
            } catch (Throwable) {
                // Preserve the optional configuration boundary.
            }
        }

        return $sources;
    }

    /**
     * Instantiates class strings and preserves PSR-11 precedence for dual-role objects.
     *
     * @param mixed $initializer an object or argument-free initializer class
     *
     * @return PsrContainerInterface|ServiceProviderInterface a source or unadapted provider
     *
     * @throws InvalidArgumentException if the initializer cannot be adapted
     */
    private function resolveCompositionSource(mixed $initializer): PsrContainerInterface|ServiceProviderInterface
    {
        if (! \is_object($initializer)) {
            if (! \is_string($initializer) || ! class_exists($initializer)) {
                throw InvalidArgumentException::forUnsupportedInitializer($initializer);
            }

            $initializer = new ($initializer)();
        }

        return match (true) {
            $initializer instanceof PsrContainerInterface => $initializer,
            $initializer instanceof ServiceProviderInterface => $initializer,
            $initializer instanceof ConfigInterface => new ConfigContainer($initializer),
            default => throw InvalidArgumentException::forUnsupportedInitializer($initializer),
        };
    }
}
