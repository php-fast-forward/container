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
 * Class InvalidArgumentException.
 *
 * Exception thrown when an argument passed to a function or method is invalid or unsupported.
 * This exception MUST be used when a container initializer does not match any of the allowed types.
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
