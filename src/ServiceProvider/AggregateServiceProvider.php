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
 */

namespace FastForward\Container\ServiceProvider;

use FastForward\Container\Exception\RuntimeException;
use FastForward\Container\Factory\ServiceFactory;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Class AggregateServiceProvider.
 *
 * Aggregates multiple service providers into a single provider.
 * This class MUST be used to compose a unified list of factories and extensions
 * from several ServiceProviderInterface implementations.
 *
 * Factories and extensions returned by this class are merged in registration order.
 *
 * @package FastForward\Container\ServiceProvider
 */
class AggregateServiceProvider implements ServiceProviderInterface
{
    /**
     * @var ServiceProviderInterface[] list of service providers to aggregate
     */
    private readonly array $serviceProviders;

    /**
     * Constructs the AggregateServiceProvider.
     *
     * @param ServiceProviderInterface ...$serviceProviders One or more service providers to aggregate.
     */
    public function __construct(ServiceProviderInterface ...$serviceProviders)
    {
        $this->serviceProviders = $serviceProviders;
    }

    /**
     * Retrieves all service factories from aggregated providers.
     *
     * This method merges the factories from each service provider into a single array.
     * The factory for this class itself is added under the key of its class name.
     *
     * @return array<string, callable> an associative array of service factories
     */
    public function getFactories(): array
    {
        return array_reduce(
            $this->serviceProviders,
            static fn ($factories, $serviceProvider) => array_merge($factories, $serviceProvider->getFactories()),
            [self::class => new ServiceFactory($this)]
        );
    }

    /**
     * Retrieves all service extensions from aggregated providers.
     *
     * This method merges extensions from each provider. If multiple extensions exist for
     * the same service ID, they are composed in the order they are added using nested closures.
     *
     * @return array<string, callable> an associative array of service extensions
     *
     * @throws \RuntimeException if any extension is not callable
     */
    public function getExtensions(): array
    {
        return array_reduce($this->serviceProviders, static function ($extensions, $serviceProvider) {
            foreach ($serviceProvider->getExtensions() as $id => $extension) {
                if (!\is_callable($extension)) {
                    throw RuntimeException::forInvalidExtension($id, get_debug_type($extension));
                }

                $extensions[$id] = !\array_key_exists($id, $extensions)
                    ? $extension
                    : static fn (ContainerInterface $container, $previous) => $extension(
                        $container,
                        $extensions[$id]($container, $previous)
                    );
            }

            return $extensions;
        }, []);
    }
}
