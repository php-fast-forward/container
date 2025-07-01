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

namespace FastForward\Container\ServiceProvider;

use Interop\Container\ServiceProviderInterface;

/**
 * Class ArrayServiceProvider.
 *
 * A simple implementation of the ServiceProviderInterface that uses plain arrays
 * to define service factories and extensions. This provider is suitable for static
 * configuration and testing scenarios where no dynamic resolution is required.
 *
 * Factories MUST be defined as an associative array where keys are service IDs and
 * values are callables. Extensions MUST also follow the same format.
 *
 * @package FastForward\Container\ServiceProvider
 */
final class ArrayServiceProvider implements ServiceProviderInterface
{
    /**
     * @var array<string, callable> an associative array of service factories
     */
    private array $factories;

    /**
     * @var array<string, callable> an associative array of service extensions
     */
    private array $extensions;

    /**
     * Constructs an ArrayServiceProvider with pre-defined factories and extensions.
     *
     * @param array<string, callable> $factories  the list of service factories
     * @param array<string, callable> $extensions the list of service extensions
     */
    public function __construct(array $factories = [], array $extensions = [])
    {
        $this->factories  = $factories;
        $this->extensions = $extensions;
    }

    public function getFactories(): array
    {
        return $this->factories;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }
}
