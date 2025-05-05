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
 */

namespace FastForward\Container\Tests\Factory;

use FastForward\Container\Exception\RuntimeException;
use FastForward\Container\Factory\MethodFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(MethodFactory::class)]
#[UsesClass(RuntimeException::class)]
final class MethodFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeInstanceMethod(): void
    {
        $service = new MethodTarget();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('prefix')->willReturn(false);
        $container->has(MethodTarget::class)->willReturn(true);
        $container->get(MethodTarget::class)->willReturn($service);

        $factory = new MethodFactory(MethodTarget::class, 'instanceMethod', 'prefix');

        $result = $factory($container->reveal());

        self::assertSame('prefix-instance', $result);
    }

    public function testInvokeStaticMethod(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(MethodTarget::class)->willReturn(false);
        $container->has('value')->willReturn(false);

        $factory = new MethodFactory(MethodTarget::class, 'staticMethod', 'value');

        $result = $factory($container->reveal());

        self::assertSame('static-value', $result);
    }

    public function testInvokeResolvesArgumentsFromContainer(): void
    {
        $service = new MethodTarget();
        $argObj  = new \stdClass();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(MethodTarget::class)->willReturn(true);
        $container->get(MethodTarget::class)->willReturn($service);
        $container->has('dependency')->willReturn(true);
        $container->get('dependency')->willReturn($argObj);

        $factory = new MethodFactory(MethodTarget::class, 'acceptsObject', 'dependency');

        $result = $factory($container->reveal());

        self::assertSame($argObj, $result);
    }

    public function testInvokeThrowsForNonPublicMethod(): void
    {
        $service = new MethodTarget();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(MethodTarget::class)->willReturn(true);
        $container->get(MethodTarget::class)->willReturn($service);

        $factory = new MethodFactory(MethodTarget::class, 'privateMethod');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method "FastForward\Container\Tests\Factory\MethodTarget::privateMethod" MUST be public to be invoked as a service.');

        $factory($container->reveal());
    }
}

class MethodTarget
{
    public function instanceMethod(string $prefix): string
    {
        return $prefix . '-instance';
    }

    public static function staticMethod(string $value): string
    {
        return 'static-' . $value;
    }

    public function acceptsObject(object $obj): object
    {
        return $obj;
    }

    private function privateMethod(): void {}
}
