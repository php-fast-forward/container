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
 * Combines explicit configuration and native constructor resolution.
 *
 * @internal
 */
final readonly class ConsumerService
{
    /**
     * @param ConsumerDependency $dependency the provider-defined dependency
     * @param OptionalDependency|null $optional the autowired optional dependency
     */
    public function __construct(
        public ConsumerDependency $dependency,
        public ?OptionalDependency $optional = null,
    ) {}
}
