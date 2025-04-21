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

namespace FastForward\Container;

use FastForward\Config\ConfigInterface;
use FastForward\Container\Factory\ContainerFactory;
use Psr\Container\ContainerInterface;

/**
 * Creates and assembles an aggregate container instance.
 *
 * This factory function SHALL accept a configuration object and a variadic list
 * of PSR-11 compliant containers. It SHALL prioritize configuration bindings and
 * autowire definitions based on provided settings.
 *
 * The function MUST return an instance of AggregateContainer that encapsulates
 * both the configuration-based container and any user-supplied containers.
 *
 * @param ConfigInterface    $config        a configuration object containing dependency definitions
 * @param ContainerInterface ...$containers Optional containers to be merged into the aggregate.
 *
 * @return ContainerInterface a fully composed container instance
 */
function container(
    ConfigInterface $config,
    ContainerInterface ...$containers,
): ContainerInterface {
    return (new ContainerFactory($config))(...$containers);
}
