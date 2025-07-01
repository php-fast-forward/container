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

namespace FastForward\Container\Tests;

use DI\Container;
use FastForward\Container\AggregateContainer;
use FastForward\Container\AutowireContainer;
use FastForward\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(AutowireContainer::class)]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(NotFoundException::class)]
final class AutowireContainerTest extends TestCase
{
    use ProphecyTrait;

    public function testGetDelegatesToInternalContainer(): void
    {
        $expected = new \stdClass();

        $delegate = $this->prophesize(ContainerInterface::class);
        $delegate->has('my.service')->willReturn(true);
        $delegate->get('my.service')->willReturn($expected);

        $container = new AutowireContainer($delegate->reveal());

        self::assertSame($expected, $container->get('my.service'));
    }

    public function testHasReturnsFalseWhenServiceNotPresent(): void
    {
        $delegate = $this->prophesize(ContainerInterface::class);
        $delegate->has('missing')->willReturn(false);

        $container = new AutowireContainer($delegate->reveal());

        self::assertFalse($container->has('missing'));
    }

    public function testHasReturnsFalseWhenResolutionFails(): void
    {
        $delegate = $this->prophesize(ContainerInterface::class);
        $delegate->has('unstable')->willReturn(true);
        $delegate->get('unstable')->willThrow(new \RuntimeException());

        $container = new AutowireContainer($delegate->reveal());

        self::assertFalse($container->has('unstable'));
    }

    public function testHasReturnsTrueWhenServiceIsResolvable(): void
    {
        $service = new \ArrayObject();

        $delegate = $this->prophesize(ContainerInterface::class);
        $delegate->has('resolvable')->willReturn(true);
        $delegate->get('resolvable')->willReturn($service);

        $container = new AutowireContainer($delegate->reveal());

        self::assertTrue($container->has('resolvable'));
    }

    public function testAutowireContainerAcceptsAggregateContainerDirectly(): void
    {
        $aggregate = $this->prophesize(AggregateContainer::class);
        $aggregate->has('resolvable')->willReturn(true);
        $aggregate->get('resolvable')->willReturn($aggregate->reveal());
        $aggregate->append(Argument::type(Container::class))->shouldBeCalledOnce();

        $container = new AutowireContainer($aggregate->reveal());

        self::assertInstanceOf(AutowireContainer::class, $container);
        self::assertSame($aggregate->reveal(), $container->get('resolvable'));
    }
}
