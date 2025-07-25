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

use FastForward\Container\Factory\InvokableFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(InvokableFactory::class)]
final class InvokableFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeInstantiatesClassWithoutArguments(): void
    {
        $factory   = new InvokableFactory(\stdClass::class);
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $result = $factory($container);

        self::assertInstanceOf(\stdClass::class, $result);
    }

    public function testInvokeInstantiatesClassWithArguments(): void
    {
        $factory   = new InvokableFactory(\DateTimeImmutable::class, '2024-01-01');
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(Argument::type('string'))->willReturn(false);

        $result = $factory($container->reveal());

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2024-01-01', $result->format('Y-m-d'));
    }

    public function testInvokeResolvesStringArgumentsFromContainer(): void
    {
        $dependency = new \stdClass();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('my.dependency')->willReturn(true);
        $container->has('literal')->willReturn(false);
        $container->get('my.dependency')->willReturn($dependency);

        $factory = new InvokableFactory(DummyService::class, 'my.dependency', 'literal');
        $service = $factory($container->reveal());

        self::assertInstanceOf(DummyService::class, $service);
        self::assertSame($dependency, $service->dependency);
        self::assertSame('literal', $service->name);
    }
}

class DummyService
{
    public function __construct(
        public readonly object $dependency,
        public readonly string $name
    ) {}
}
