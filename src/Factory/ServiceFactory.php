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

namespace FastForward\Container\Factory;

use Psr\Container\ContainerInterface;

/**
 * This factory wraps a predefined service instance and returns it directly upon invocation.
 *
 * It SHALL be used when a fixed service object must be registered in a container using
 * a factory interface.
 *
 * The returned value MUST be the exact same instance provided at construction.
 *
 * This ensures immutability and predictable resolution.
 *
 * @package FastForward\Container\Factory
 */
final class ServiceFactory implements FactoryInterface
{
    /**
     * @var mixed The fixed service instance to return when invoked.
     *            This value MUST NOT change after instantiation.
     */
    private readonly mixed $service;

    /**
     * Constructs the factory with a fixed service instance.
     *
     * @param mixed $service the service instance to be returned by the factory
     */
    public function __construct(mixed $service)
    {
        $this->service = $service;
    }

    /**
     * Returns the fixed service instance.
     *
     * @param ContainerInterface $container the container instance (ignored in this context)
     *
     * @return mixed the predefined service instance
     */
    public function __invoke(ContainerInterface $container): mixed
    {
        return $this->service;
    }
}
