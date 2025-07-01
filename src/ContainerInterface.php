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

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Interface ContainerInterface
 *
 * Extends the PSR-11 ContainerInterface to provide a consistent, domain-specific container interface
 * for the FastForward ecosystem. This interface SHALL serve as the preferred type hint within FastForward
 * components, while maintaining full compatibility with PSR-11 standards.
 *
 * Implementations of this interface MUST adhere to the behavior defined by PSR-11, specifically:
 *
 * - `get(string $id)` MUST return an entry if available, or throw a `NotFoundExceptionInterface`.
 * - `has(string $id)` MUST return true if the entry can be resolved, false otherwise.
 *
 * This abstraction MAY be extended in the future to incorporate additional container-related functionality
 * specific to the FastForward framework, without violating PSR-11 compatibility.
 *
 * @package FastForward\Container
 */
interface ContainerInterface extends PsrContainerInterface {}
