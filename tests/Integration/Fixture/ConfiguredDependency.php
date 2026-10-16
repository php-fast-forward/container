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
 * Keeps scalar configuration behind an explicit provider factory.
 *
 * @internal
 */
final readonly class ConfiguredDependency implements ConsumerDependency
{
    /**
     * @param string $applicationName the value read through config.*
     */
    public function __construct(
        private string $applicationName
    ) {}

    /**
     * @return string the configured application name
     */
    public function name(): string
    {
        return $this->applicationName;
    }
}
