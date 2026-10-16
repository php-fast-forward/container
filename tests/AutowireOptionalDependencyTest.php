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

namespace FastForward\Container\Tests;

use FastForward\Container\AggregateContainer;
use FastForward\Container\Autowire\ReflectionContainer;
use FastForward\Container\AutowireContainer;
use FastForward\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AutowireContainer::class)]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(ReflectionContainer::class)]
#[UsesClass(NotFoundException::class)]
final class AutowireOptionalDependencyTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function optionalConcreteDependencyIsAutowiredBeforeUsingNullDefault(): void
    {
        $container = new AutowireContainer(new AggregateContainer());

        self::assertSame(
            $container->get(OptionalDependency::class),
            $container->get(OptionalConsumer::class)->dependency,
        );
    }
}

final class OptionalDependency {}

final readonly class OptionalConsumer
{
    /**
     * @param OptionalDependency|null $dependency
     */
    public function __construct(
        public ?OptionalDependency $dependency = null
    ) {}
}
