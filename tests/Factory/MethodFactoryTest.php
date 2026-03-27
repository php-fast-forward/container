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

use stdClass;
use Exception;
use FastForward\Container\Exception\RuntimeException;
use FastForward\Container\Factory\MethodFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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

    /**
     * @return void
     */
    #[Test]
    public function invokeInstanceMethod(): void
    {
        $service = new MethodFactoryTestTargetStub();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('prefix')
            ->willReturn(false);
        $container->has(MethodFactoryTestTargetStub::class)->willReturn(true);
        $container->get(MethodFactoryTestTargetStub::class)->willReturn($service);

        $factory = new MethodFactory(MethodFactoryTestTargetStub::class, 'instanceMethod', 'prefix');

        $result = $factory($container->reveal());

        self::assertSame('prefix-instance', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function invokeStaticMethod(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('value')
            ->willReturn(false);

        $factory = new MethodFactory(MethodFactoryTestTargetStub::class, 'staticMethod', 'value');

        $result = $factory($container->reveal());

        self::assertSame('static-value', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function invokeResolvesArgumentsFromContainer(): void
    {
        $service = new MethodFactoryTestTargetStub();
        $argObj  = new stdClass();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(MethodFactoryTestTargetStub::class)->willReturn(true);
        $container->get(MethodFactoryTestTargetStub::class)->willReturn($service);
        $container->has('dependency')
            ->willReturn(true);
        $container->get('dependency')
            ->willReturn($argObj);

        $factory = new MethodFactory(MethodFactoryTestTargetStub::class, 'acceptsObject', 'dependency');

        $result = $factory($container->reveal());

        self::assertSame($argObj, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function invokeThrowsForNonPublicMethod(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(MethodFactoryTestTargetStub::class)->willReturn(false);

        $factory = new MethodFactory(MethodFactoryTestTargetStub::class, 'privateMethod');

        $this->expectException(RuntimeException::class);

        $factory($container->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function invokeWillConstructTargetIfContainerDoesNotProvide(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('prefix')
            ->willReturn(false);
        $container->get(MethodFactoryTestTargetStub::class)->willThrow(new Exception());

        $factory = new MethodFactory(MethodFactoryTestTargetStub::class, 'instanceMethod', 'prefix');

        $result = $factory($container->reveal());

        self::assertSame('prefix-instance', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorDependencyIsResolvedFromContainer(): void
    {
        $dependency = new MethodFactoryTestDependencyStub();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(MethodFactoryTestTargetStub::class)->willReturn(true);
        $container->get(MethodFactoryTestTargetStub::class)->willReturn(
            new MethodFactoryTestTargetStub($dependency)
        )->shouldBeCalledOnce();
        $container->has('suffix')
            ->willReturn(false);

        $factory = new MethodFactory(MethodFactoryTestTargetStub::class, 'usesConstructorArgument', 'suffix');

        $result = $factory($container->reveal());

        self::assertSame(MethodFactoryTestDependencyStub::class . '-suffix', $result);
    }
}

class MethodFactoryTestTargetStub
{
    /**
     * @param MethodFactoryTestDependencyStub|null $dependency
     */
    public function __construct(
        private readonly ?MethodFactoryTestDependencyStub $dependency = null
    ) {
        $this->privateMethod();
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    public function instanceMethod(string $prefix): string
    {
        return $prefix . '-instance';
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public static function staticMethod(string $value): string
    {
        return 'static-' . $value;
    }

    /**
     * @param object $obj
     *
     * @return object
     */
    public function acceptsObject(object $obj): object
    {
        return $obj;
    }

    /**
     * @param string $suffix
     *
     * @return string
     */
    public function usesConstructorArgument(string $suffix): string
    {
        return $this->dependency::class . '-' . $suffix;
    }

    /**
     * @return void
     */
    private function privateMethod(): void {}
}

class MethodFactoryTestDependencyStub {}
