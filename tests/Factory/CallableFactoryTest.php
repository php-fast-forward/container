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

namespace FastForward\Container\Tests\Factory;

use FastForward\Container\Exception\RuntimeException;
use FastForward\Container\Factory\CallableFactory;
use FastForward\Container\Factory\FactoryInterface;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(CallableFactory::class)]
#[UsesClass(RuntimeException::class)]
final class CallableFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeWillReturnProvidedCallableReturns(): void
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $factory = new CallableFactory(static fn () => (object) ['resolved' => true]);

        $result = $factory($container);

        self::assertIsObject($result);
        self::assertTrue($result->resolved);
    }

    public function testClosureReceivesContainerDependenciesAsArgument(): void
    {
        $container        = $this->prophesize(ContainerInterface::class);
        $factoryInterface = $this->prophesize(FactoryInterface::class)->reveal();
        $serviceProvider  = $this->prophesize(ServiceProviderInterface::class)->reveal();

        $container->get(ServiceProviderInterface::class)->willReturn($serviceProvider);
        $container->get(FactoryInterface::class)->willReturn($factoryInterface);

        $factory = new CallableFactory(static fn (
            ServiceProviderInterface $serviceProvider,
            FactoryInterface $factoryInterface
        ) => compact('serviceProvider', 'factoryInterface'));

        $actual = $factory($container->reveal());

        self::assertSame(compact('serviceProvider', 'factoryInterface'), $actual);
    }

    public function testInvokeWillThrowRuntimeExceptionIfParameterIsNotAClass(): void
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $factory = new CallableFactory(static fn (string $notAClass) => $notAClass);

        $this->expectException(RuntimeException::class);

        $factory($container);
    }
}
