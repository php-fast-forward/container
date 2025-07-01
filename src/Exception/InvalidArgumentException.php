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

/**
 * Exception thrown when an invalid or unsupported argument is passed to a function or method within the container.
 *
 * This exception helps identify and handle errors related to invalid or unrecognized arguments,
 * especially when an unsupported initializer type is provided to the container builder.
 *
 * @package FastForward\Container\Exception
 */
final class InvalidArgumentException extends \InvalidArgumentException
{
    /**
     * Creates an exception indicating an unsupported container initializer.
     *
     * This method SHALL be used to indicate that an unrecognized initializer type
     * was passed to the container builder function.
     *
     * @param mixed $value the value that was identified as unsupported
     *
     * @return self a new InvalidArgumentException with a descriptive message
     */
    public static function forUnsupportedInitializer(mixed $value): self
    {
        return new self(\sprintf(
            'Unsupported initializer type: %s',
            get_debug_type($value)
        ));
    }
}
