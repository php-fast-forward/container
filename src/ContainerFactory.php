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
 * Composes a fresh container through the incremental ContainerBuilder API.
 *
 * This factory SHALL register the supplied initializers with a new builder and
 * delegate assembly to it. The factory MUST NOT retain assembled container state.
 * Source order, provider wrapper injection, and optional configuration policy
 * SHALL remain consistent with direct builder composition.
 */
final class ContainerFactory
{
    /**
     * Creates and assembles a fresh container stack.
     *
     * This method SHALL pass initializers to a new ContainerBuilder in supplied order.
     * Class names MUST be instantiable without required constructor arguments.
     * Supplied PSR-11 containers SHALL be reused without cloning their state.
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
        return (new ContainerBuilder())->add(...$initializers)->build();
    }
}
