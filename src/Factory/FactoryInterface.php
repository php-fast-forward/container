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

namespace FastForward\Container\Factory;

use Psr\Container\ContainerInterface;

/**
 * Defines a contract for service factories that rely on a PSR-11 container for instantiation.
 *
 * Implementing classes MUST implement the __invoke method which SHALL be responsible for returning
 * the fully constructed service instance.
 *
 * This interface is commonly used in container-based systems to register factories dynamically.
 */
interface FactoryInterface
{
    /**
     * Creates a service instance using the provided container.
     *
     * Implementations MUST resolve all necessary dependencies from the container
     * and return a fully constructed instance.
     *
     * @param ContainerInterface $container the PSR-11 compliant container providing dependencies
     *
     * @return mixed the created service instance
     */
    public function __invoke(ContainerInterface $container): mixed;
}
