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

namespace FastForward\Container\Tests\Factory;

use FastForward\Container\Exception\RuntimeException;
use FastForward\Container\Factory\CallableFactory;
use FastForward\Container\Factory\FactoryInterface;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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

    /**
     * @return void
     */
    #[Test]
    public function invokeWillReturnProvidedCallableReturns(): void
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $factory = new CallableFactory(static fn() => (object) [
            'resolved' => true,
        ]);

        $result = $factory($container);

        self::assertIsObject($result);
        self::assertTrue($result->resolved);
    }

    /**
     * @return void
     */
    #[Test]
    public function closureReceivesContainerDependenciesAsArgument(): void
    {
        $container        = $this->prophesize(ContainerInterface::class);
        $factoryInterface = $this->prophesize(FactoryInterface::class)->reveal();
        $serviceProvider  = $this->prophesize(ServiceProviderInterface::class)->reveal();

        $container->get(ServiceProviderInterface::class)->willReturn($serviceProvider);
        $container->get(FactoryInterface::class)->willReturn($factoryInterface);

        $factory = new CallableFactory(static fn(
            ServiceProviderInterface $serviceProvider,
            FactoryInterface $factoryInterface
        ): array => [
            'serviceProvider' => $serviceProvider,
            'factoryInterface' => $factoryInterface,
        ]);

        $actual = $factory($container->reveal());

        self::assertSame([
            'serviceProvider' => $serviceProvider,
            'factoryInterface' => $factoryInterface,
        ], $actual);
    }

    /**
     * @return void
     */
    #[Test]
    public function invokeWillThrowRuntimeExceptionIfParameterIsNotAClass(): void
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $factory = new CallableFactory(static fn(string $notAClass): string => $notAClass);

        $this->expectException(RuntimeException::class);

        $factory($container);
    }
}
