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

use stdClass;
use Exception;
use FastForward\Container\AggregateContainer;
use FastForward\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @internal
 */
#[CoversClass(AggregateContainer::class)]
#[UsesClass(NotFoundException::class)]
final class AggregateContainerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function hasReturnsTrueForAliasAndClassBindings(): void
    {
        $container = new AggregateContainer();

        self::assertTrue($container->has(AggregateContainer::ALIAS));
        self::assertTrue($container->has(AggregateContainer::class));
        self::assertTrue($container->has(ContainerInterface::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasReturnsFalseForUnknownKey(): void
    {
        $container = new AggregateContainer();

        self::assertFalse($container->has(uniqid('unknown_', true)));
    }

    /**
     * @return void
     */
    #[Test]
    public function getReturnsSelfForAliasAndClassBindings(): void
    {
        $container = new AggregateContainer();

        self::assertSame($container, $container->get(AggregateContainer::ALIAS));
        self::assertSame($container, $container->get(AggregateContainer::class));
        self::assertSame($container, $container->get(ContainerInterface::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasReturnsTrueIfSubContainerHasEntry(): void
    {
        $key = uniqid('svc_', true);

        $sub = $this->prophesize(ContainerInterface::class);
        $sub->has($key)
            ->willReturn(true);

        $container = new AggregateContainer($sub->reveal());

        self::assertTrue($container->has($key));
    }

    /**
     * @return void
     */
    #[Test]
    public function getReturnsResolvedEntryFromSubContainer(): void
    {
        $key   = uniqid('dep_', true);
        $value = uniqid('value_', true);

        $sub = $this->prophesize(ContainerInterface::class);
        $sub->has($key)
            ->willReturn(true);
        $sub->get($key)
            ->willReturn($value);

        $container = new AggregateContainer($sub->reveal());

        self::assertSame($value, $container->get($key));
        self::assertSame($value, $container->get($key)); // ensures internal caching
    }

    /**
     * @return void
     */
    #[Test]
    public function getSkipsContainersThrowingNotFoundException(): void
    {
        $key   = uniqid('missing_', true);
        $value = uniqid('fallback_', true);

        $first = $this->prophesize(ContainerInterface::class);
        $first->has($key)
            ->willReturn(true);
        $first->get($key)
            ->willThrow(NotFoundException::class);

        $second = $this->prophesize(ContainerInterface::class);
        $second->has($key)
            ->willReturn(true);
        $second->get($key)
            ->willReturn($value);

        $container = new AggregateContainer($first->reveal(), $second->reveal());

        self::assertSame($value, $container->get($key));
    }

    /**
     * @return void
     */
    #[Test]
    public function getSkipsContainersThrowingStandardInterfaces(): void
    {
        $key   = uniqid('service_', true);
        $value = new stdClass();

        $nf = new class extends Exception implements NotFoundExceptionInterface {};
        $ce = new class extends Exception implements ContainerExceptionInterface {};

        $first = $this->prophesize(ContainerInterface::class);
        $first->has($key)
            ->willReturn(true);
        $first->get($key)
            ->willThrow($nf);

        $second = $this->prophesize(ContainerInterface::class);
        $second->has($key)
            ->willReturn(true);
        $second->get($key)
            ->willThrow($ce);

        $third = $this->prophesize(ContainerInterface::class);
        $third->has($key)
            ->willReturn(true);
        $third->get($key)
            ->willReturn($value);

        $container = new AggregateContainer($first->reveal(), $second->reveal(), $third->reveal());

        self::assertSame($value, $container->get($key));
    }

    /**
     * @return void
     */
    #[Test]
    public function getThrowsWhenNoContainerCanResolve(): void
    {
        $this->expectException(NotFoundException::class);

        $key = uniqid('unavailable_', true);

        $c1 = $this->prophesize(ContainerInterface::class);
        $c1->has($key)
            ->willReturn(false);

        $c2 = $this->prophesize(ContainerInterface::class);
        $c2->has($key)
            ->willReturn(false);

        $container = new AggregateContainer($c1->reveal(), $c2->reveal());

        $container->get($key);
    }

    /**
     * @return void
     */
    #[Test]
    public function appendAddsContainerAtTheEnd(): void
    {
        $key   = uniqid('svc_', true);
        $value = uniqid('val_', true);

        $first = $this->prophesize(ContainerInterface::class);
        $first->has($key)
            ->willReturn(false);

        $second = $this->prophesize(ContainerInterface::class);
        $second->has($key)
            ->willReturn(true);
        $second->get($key)
            ->willReturn($value);

        $container = new AggregateContainer($first->reveal());
        $container->append($second->reveal());

        self::assertTrue($container->has($key));
        self::assertSame($value, $container->get($key));
    }

    /**
     * @return void
     */
    #[Test]
    public function prependAddsContainerAtTheBeginning(): void
    {
        $key   = uniqid('service_', true);
        $value = uniqid('val_', true);

        $first = $this->prophesize(ContainerInterface::class);
        $first->has($key)
            ->willReturn(true);
        $first->get($key)
            ->willReturn($value);

        $second = $this->prophesize(ContainerInterface::class);
        $second->has($key)
            ->willReturn(true);
        $second->get($key)
            ->willReturn('incorrect');

        $container = new AggregateContainer($second->reveal());
        $container->prepend($first->reveal());

        self::assertTrue($container->has($key));
        self::assertSame($value, $container->get($key));
    }

    /**
     * @return void
     */
    #[Test]
    public function getUsesInternalCacheAfterFirstResolution(): void
    {
        $key   = 'shared.service';
        $value = uniqid('resolved_', true);

        $sub = $this->prophesize(ContainerInterface::class);
        $sub->has($key)
            ->willReturn(true);
        $sub->get($key)
            ->willReturn($value)
            ->shouldBeCalledTimes(1);

        $container = new AggregateContainer($sub->reveal());

        self::assertSame($value, $container->get($key));
        self::assertSame($value, $container->get($key)); // resolved from cache, not re-fetched
    }
}
