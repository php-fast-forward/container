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

namespace FastForward\Container\Exception;

use RuntimeException;
use Psr\Container\ContainerExceptionInterface;

/**
 * Signals a constructor dependency graph that cannot be inferred.
 *
 * Optional arguments MAY use their defaults for this structural failure.
 * Execution failures and cycles MUST use ContainerException instead.
 */
final class AutowiringException extends RuntimeException implements ContainerExceptionInterface {}
