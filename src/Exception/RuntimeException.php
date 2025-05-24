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
 * such as misconfigured extensions or illegal method accessibility.
 *
 * @package FastForward\Container\Exception
 */
final class RuntimeException extends \RuntimeException
{
    /**
     * Creates an exception for a non-callable service extension.
     *
     * This method MUST be used when a value intended to act as a service extension
     * is not callable, violating the expected contract of container extensions.
     *
     * @param string $service the identifier of the service with the invalid extension
     * @param string $given   the type or class name of the invalid value
     *
     * @return self a RuntimeException instance with a descriptive message
     */
    public static function forNonCallableExtension(string $service, string $given): self
    {
        return new self(\sprintf(
            'Service "%s" extension MUST be callable, "%s" given.',
            $service,
            $given
        ));
    }

    /**
     * Creates an exception for attempting to use a non-public method.
     *
     * This method SHOULD be used when trying to invoke a method that is not declared public,
     * thereby violating service visibility requirements.
     *
     * @param string $class  the fully qualified class name
     * @param string $method the name of the method that is not publicly accessible
     *
     * @return self a RuntimeException indicating the method MUST be public
     */
    public static function forNonPublicMethod(string $class, string $method): self
    {
        return new self(\sprintf(
            'Method "%s::%s" MUST be public to be invoked as a service.',
            $class,
            $method
        ));
    }

    /**
     * Creates an exception for an invalid parameter type.
     *
     * This method MUST be used when a parameter expected to represent a class name
     * or interface name does not satisfy this constraint.
     *
     * @param string $parameter the name of the parameter with the invalid type
     *
     * @return self a RuntimeException instance with a descriptive message
     */
    public static function forInvalidParameterType(string $parameter): self
    {
        return new self(\sprintf(
            'Parameter "%s" is not a valid type. It MUST be a class name or an interface name.',
            $parameter
        ));
    }
}
