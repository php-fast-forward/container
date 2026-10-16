<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/container.
 *
 * @copyright Copyright (c) 2025-2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FastForward\Container\Tests\Integration\Fixture;

/**
 * The explicitly configured dependency in the production consumer.
 *
 * @internal
 */
interface ConsumerDependency
{
    /**
     * @return string the configured application name
     */
    public function name(): string;
}
