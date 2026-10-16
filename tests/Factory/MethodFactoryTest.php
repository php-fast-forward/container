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
use Error;
use LogicException;
use ReflectionException;
use Throwable;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Exception\RuntimeException;
use FastForward\Container\Factory\MethodFactory;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use FastForward\Container\Tests\Fixtures\Factory\MethodFactoryTestTargetStub;
use FastForward\Container\Tests\Fixtures\Factory\MethodFactoryTestDependencyStub;
use PHPUnit\Framework\Attributes\DataProvider;
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
#[UsesClass(ContainerException::class)]
#[UsesClass(NotFoundException::class)]
#[UsesClass(ArrayServiceProvider::class)]
#[UsesClass(ServiceProviderContainer::class)]
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
        $container->get(MethodFactoryTestTargetStub::class)->willThrow(
            NotFoundException::forServiceID(MethodFactoryTestTargetStub::class)
        );

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

    /**
     * @return void
     */
    #[Test]
    public function invokesConfiguredTargetThroughANonClassServiceId(): void
    {
        $dependency = new MethodFactoryTestDependencyStub();
        $target = new MethodFactoryTestTargetStub($dependency);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('factory.target')
            ->willReturn($target)
            ->shouldBeCalledOnce();
        $container->has('suffix')
            ->willReturn(false);

        $result = (new MethodFactory('factory.target', 'usesConstructorArgument', 'suffix'))($container->reveal());

        self::assertSame(MethodFactoryTestDependencyStub::class . '-suffix', $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function resolvesAnInterfaceBindingUsingItsActualObject(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ContainerInterface::class)->willReturn($container->reveal());
        $container->has('entry')
            ->willReturn(false);

        self::assertFalse((new MethodFactory(ContainerInterface::class, 'has', 'entry'))($container->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function rejectsNonPublicMethodsOnAServiceIdTarget(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('factory.target')
            ->willReturn(new MethodFactoryTestTargetStub());
        $this->expectException(RuntimeException::class);

        (new MethodFactory('factory.target', 'privateMethod'))($container->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function rejectsAnUnknownMethodOnAServiceIdTarget(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('factory.target')
            ->willReturn(new MethodFactoryTestTargetStub());
        $this->expectException(ReflectionException::class);

        (new MethodFactory('factory.target', 'missingMethod'))($container->reveal());
    }

    /**
     * @return iterable<string, array{Throwable}> failures that must never trigger a new target
     */
    public static function resolutionFailures(): iterable
    {
        yield 'container failure' => [new ContainerException('registered factory failed')];
        yield 'execution exception' => [new LogicException('invalid target configuration')];
        yield 'execution error' => [new Error('target initialization failed')];
    }

    /**
     * @param Throwable $failure the original failure resolving a class-name target
     */
    #[DataProvider('resolutionFailures')]
    public function testExistingTargetFailuresPropagateUnchanged(Throwable $failure): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with(MethodFactoryTestTargetStub::class)->willThrowException(
            $failure
        );

        try {
            (new MethodFactory(MethodFactoryTestTargetStub::class, 'instanceMethod'))($container);
            self::fail('A failed target resolution must not construct an unconfigured replacement.');
        } catch (Throwable $throwable) {
            self::assertSame($failure, $throwable);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function retainsTheCauseOfARegisteredFactoryFailure(): void
    {
        $failure = new LogicException('registered constructor configuration failed');
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            MethodFactoryTestTargetStub::class => static fn(): never => throw $failure,
        ]));

        try {
            (new MethodFactory(MethodFactoryTestTargetStub::class, 'instanceMethod'))($container);
            self::fail('The broken provider factory must remain an error.');
        } catch (ContainerException $containerException) {
            self::assertSame($failure, $containerException->getPrevious());
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function retainsNotFoundForANonClassServiceId(): void
    {
        $failure = NotFoundException::forServiceID('missing.target');
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('missing.target')
            ->willThrow($failure);

        $this->expectExceptionObject($failure);

        (new MethodFactory('missing.target', 'instanceMethod'))($container->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function preservesNamedMethodArguments(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(MethodFactoryTestTargetStub::class)->willReturn(new MethodFactoryTestTargetStub());
        $container->has('literal')
            ->willReturn(false);

        $factory = new MethodFactory(MethodFactoryTestTargetStub::class, 'instanceMethod', prefix: 'literal');

        self::assertSame('literal-instance', $factory($container->reveal()));
    }
}
