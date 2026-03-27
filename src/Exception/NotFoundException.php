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

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when a requested service identifier is not found in the container.
 *
 * This class MUST be used in PSR-11 container implementations to represent an error
 * condition where a service ID does not exist in the container. It implements the
 * Psr\Container\NotFoundExceptionInterface to guarantee interoperability with PSR-11 consumers.
 */
final class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * Creates a new NotFoundException for a missing service identifier.
     *
     * This factory method SHOULD be used by the container implementation to report
     * the absence of a given service ID. The resulting exception message SHALL clearly
     * indicate which identifier was not resolved.
     *
     * @param string $id the service identifier that was not found
     *
     * @return self an instance of NotFoundException describing the missing service
     */
    public static function forServiceID(string $id): self
    {
        return new self(\sprintf('Service "%s" not found.', $id));
    }
}
