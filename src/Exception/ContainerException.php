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

namespace FastForward\Container\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Exception type for container-related errors while resolving services.
 *
 * This class MUST be used to signal problems occurring during service resolution
 * from a container that complies with PSR-11.
 *
 * @package FastForward\Container\Exception
 */
final class ContainerException extends \Exception implements ContainerExceptionInterface
{
    /**
     * Creates an exception for an invalid or unresolvable service identifier.
     *
     * This factory method MUST be invoked when a service ID fails to resolve
     * or the resolved service is invalid in the current context.
     *
     * @param string     $id       the identifier of the service that caused the failure
     * @param \Throwable $previous the previous exception thrown during resolution
     *
     * @return self an instance of ContainerException describing the issue
     */
    public static function forInvalidService(string $id, \Throwable $previous): self
    {
        return new self(\sprintf('Invalid service "%s".', $id), $previous->getCode(), $previous);
    }
}
