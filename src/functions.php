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

use FastForward\Config\ConfigInterface;
use FastForward\Container\Exception\InvalidArgumentException;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Creates and assembles a fully composed container instance.
 *
 * This convenience function delegates composition to ContainerFactory::create().
 * Accepted initializers include:
 * - PSR-11 container instances
 * - Service providers implementing ServiceProviderInterface
 * - Configuration instances implementing ConfigInterface
 * - Class names as strings (which MUST be instantiable)
 *
 * If a ConfigContainer is included, it SHALL attempt to resolve additional nested container
 * definitions from its configuration using the key `${alias}.${ContainerInterface::class}`.
 * Unknown initializer classes and unsupported constructed objects MUST result in
 * an InvalidArgumentException being thrown.
 *
 * The final container returned is an AutowireContainer that wraps an
 * AggregateContainer composed of all resolved sources.
 * Providers compose by default: the first callable factory wins and every provider
 * extension runs in registration order after selection, including earlier extensions.
 * Selected provider failures propagate; external sources remain independent.
 *
 * @param ConfigInterface|PsrContainerInterface|ServiceProviderInterface|string ...$initializers
 *                                                                                               A variadic list of container initializers, optionally including config or provider classes.
 *
 * @return ContainerInterface the composed and autowire-enabled container
 *
 * @throws InvalidArgumentException if an initializer class cannot be found or adapted
 */
function container(
    ConfigInterface|PsrContainerInterface|ServiceProviderInterface|string ...$initializers,
): ContainerInterface {
    return ContainerFactory::create(...$initializers);
}
