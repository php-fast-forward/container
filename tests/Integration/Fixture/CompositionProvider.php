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

namespace FastForward\Container\Tests\Integration\Fixture;

use stdClass;
use Interop\Container\ServiceProviderInterface;

/**
 * Argument-free initializer used by isolated production composition tests.
 */
final class CompositionProvider implements ServiceProviderInterface
{
    /**
     * @return array<string, callable>
     */
    public function getFactories(): array
    {
        return [
            'entry' => static fn(): string => 'provider',
            'object' => static fn(): stdClass => new stdClass(),
        ];
    }

    /**
     * @return array<string, callable>
     */
    public function getExtensions(): array
    {
        return [];
    }
}
