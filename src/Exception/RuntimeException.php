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

namespace FastForward\Container\Exception;

/**
 * Class RuntimeException.
 *
 * Exception type used to represent runtime errors specific to the container context.
 * This class MUST be thrown when an error occurs due to invalid runtime behavior
 * such as misconfigured extensions or unresolved services.
 *
 * @package FastForward\Container\Exception
 */
final class RuntimeException extends \RuntimeException
{
    /**
     * Creates an exception indicating an invalid extension definition.
     *
     * This method MUST be used when a service extension is expected to be callable,
     * but a non-callable value is provided instead.
     *
     * @param string $service the identifier of the service with the invalid extension
     * @param string $given   the type or class name of the invalid value
     */
    public static function forInvalidExtension(string $service, string $given): self
    {
        return new self(\sprintf('Extension for "%s" must be callable, "%s" given.', $service, $given));
    }
}
