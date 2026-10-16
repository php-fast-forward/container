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
use Throwable;
use Psr\Container\ContainerExceptionInterface;

/**
 * Signals a constructor dependency graph that cannot be inferred.
 *
 * Optional arguments MAY use their defaults for this structural failure.
 * Execution failures and cycles MUST use ContainerException instead.
 */
final class AutowiringException extends RuntimeException implements ContainerExceptionInterface
{
    /**
     * Creates an exception for a constructor parameter without an inferable value.
     *
     * @param string $class the class that declares the constructor
     * @param string $parameter the parameter name
     * @param string|null $type the declared type, or null for an untyped parameter
     * @param Throwable|null $previous the structural dependency resolution failure
     *
     * @return self an exception identifying the parameter and preserving its cause
     */
    public static function forUnresolvableParameter(
        string $class,
        string $parameter,
        ?string $type,
        ?Throwable $previous = null
    ): self {
        return new self(\sprintf(
            'Cannot autowire %s::__construct(): parameter $%s (%s) requires an explicit service or factory.',
            $class,
            $parameter,
            $type ?? 'untyped',
        ), 0, $previous);
    }
}
