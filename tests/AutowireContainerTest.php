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
use RuntimeException;
use ArrayObject;
use FastForward\Container\Autowire\ReflectionContainer;
use FastForward\Container\AggregateContainer;
use FastForward\Container\AutowireContainer;
use FastForward\Container\Exception\AutowiringException;
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
#[UsesClass(ReflectionContainer::class)]
#[UsesClass(NotFoundException::class)]
#[UsesClass(AutowiringException::class)]
#[UsesClass(ContainerException::class)]
#[UsesClass(ArrayServiceProvider::class)]
#[UsesClass(ServiceProviderContainer::class)]
final class AutowireContainerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param list<null> $delegateArguments
     *
     * @return void
     */
    #[Test]
    #[DataProvider('standaloneDelegates')]
    public function standaloneContainerResolvesAndSharesConcreteDependencies(array $delegateArguments): void
    {
        $container = new AutowireContainer(...$delegateArguments);
        $service = $container->get(AutowireOnlyService::class);

        self::assertInstanceOf(AutowireOnlyDependency::class, $service->dependency);
        self::assertSame($container->get(AutowireOnlyLeaf::class), $service->dependency->leaf);
        self::assertSame($service->dependency->leaf, $service->optionalLeaf);
        self::assertSame($service, $container->get(AutowireOnlyService::class));
        self::assertTrue($container->has(AutowireOnlyService::class));
        self::assertFalse($container->has('missing.standalone.service'));
    }

    /**
     * @return iterable<string, array{list<null>}>
     */
    public static function standaloneDelegates(): iterable
    {
        yield 'omitted delegate' => [[]];
        yield 'explicit null delegate' => [[null]];
    }

    /**
     * @return void
     */
    #[Test]
    public function standaloneContainersHaveIndependentInstances(): void
    {
        $first = new AutowireContainer();
        $second = new AutowireContainer();

        self::assertNotSame($first->get(AutowireOnlyLeaf::class), $second->get(AutowireOnlyLeaf::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function delegateEntriesTakePrecedenceForDirectAndNestedDependencies(): void
    {
        $expected = new AutowireOnlyLeaf();
        $delegate = new ServiceProviderContainer(new ArrayServiceProvider([
            AutowireOnlyLeaf::class => static fn(): AutowireOnlyLeaf => $expected,
        ]));
        $container = new AutowireContainer($delegate);
        $service = $container->get(AutowireOnlyService::class);

        self::assertSame($expected, $container->get(AutowireOnlyLeaf::class));
        self::assertSame($expected, $service->dependency->leaf);
        self::assertSame($expected, $service->optionalLeaf);
        self::assertSame($service, $container->get(AutowireOnlyService::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function getDelegatesToInternalContainer(): void
    {
        $expected = new stdClass();

        $delegate = $this->prophesize(ContainerInterface::class);
        $delegate->has('my.service')
            ->willReturn(true);
        $delegate->get('my.service')
            ->willReturn($expected);

        $container = new AutowireContainer($delegate->reveal());

        self::assertSame($expected, $container->get('my.service'));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasReturnsFalseWhenServiceNotPresent(): void
    {
        $delegate = $this->prophesize(ContainerInterface::class);
        $delegate->has('missing')
            ->willReturn(false);

        $container = new AutowireContainer($delegate->reveal());

        self::assertFalse($container->has('missing'));

        $this->expectException(NotFoundException::class);

        $container->get('missing');
    }

    /**
     * @return void
     */
    #[Test]
    public function hasDoesNotAttemptKnownDelegateResolution(): void
    {
        $delegate = $this->prophesize(ContainerInterface::class);
        $delegate->has('unstable')
            ->willReturn(true);
        $delegate->get('unstable')
            ->shouldNotBeCalled();

        $container = new AutowireContainer($delegate->reveal());

        self::assertTrue($container->has('unstable'));
    }

    /**
     * @return void
     */
    #[Test]
    public function knownClassWithUninferableArgumentsKeepsItsConstructionError(): void
    {
        $container = new AutowireContainer();

        self::assertTrue($container->has(AutowireRequiredScalar::class));

        $this->expectException(AutowiringException::class);
        $this->expectExceptionMessage('parameter $label');

        $container->get(AutowireRequiredScalar::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function knownThrowingConstructorIsOnlyExecutedByGet(): void
    {
        AutowireThrowingConstructor::$attempts = 0;
        $container = new AutowireContainer();

        self::assertTrue($container->has(AutowireThrowingConstructor::class));
        self::assertSame(0, AutowireThrowingConstructor::$attempts);

        try {
            $container->get(AutowireThrowingConstructor::class);
            self::fail('A known service must preserve its constructor failure.');
        } catch (ContainerException $containerException) {
            self::assertInstanceOf(RuntimeException::class, $containerException->getPrevious());
            self::assertSame(1, AutowireThrowingConstructor::$attempts);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function knownFactoryIsOnlyExecutedByGetAndPreservesItsFailure(): void
    {
        $previous = new RuntimeException('factory failed');
        $container = new AutowireContainer(new ServiceProviderContainer(new ArrayServiceProvider([
            'broken.factory' => static fn() => throw $previous,
        ])));

        self::assertTrue($container->has('broken.factory'));

        try {
            $container->get('broken.factory');
            self::fail('A known service must preserve its factory failure.');
        } catch (ContainerException $containerException) {
            self::assertSame($previous, $containerException->getPrevious());
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function knownOptionalCycleKeepsItsCycleError(): void
    {
        $container = new AutowireContainer();

        self::assertTrue($container->has(AutowireOptionalCycle::class));

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(AutowireOptionalCycle::class . ' -> ' . AutowireOptionalCycle::class);

        $container->get(AutowireOptionalCycle::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function hasReturnsTrueWhenServiceIsResolvable(): void
    {
        $service = new ArrayObject();

        $delegate = $this->prophesize(ContainerInterface::class);
        $delegate->has('resolvable')
            ->willReturn(true);
        $delegate->get('resolvable')
            ->willReturn($service);

        $container = new AutowireContainer($delegate->reveal());

        self::assertTrue($container->has('resolvable'));
    }

    /**
     * @return void
     */
    #[Test]
    public function autowireContainerAcceptsAggregateContainerDirectly(): void
    {
        $aggregate = $this->prophesize(AggregateContainer::class);
        $aggregate->has('resolvable')
            ->willReturn(true);
        $aggregate->get('resolvable')
            ->willReturn($aggregate->reveal());
        $aggregate->append(Argument::type(ReflectionContainer::class))->shouldBeCalledOnce();

        $container = new AutowireContainer($aggregate->reveal());

        self::assertInstanceOf(AutowireContainer::class, $container);
        self::assertSame($aggregate->reveal(), $container->get('resolvable'));
    }
}

final readonly class AutowireOnlyService
{
    /**
     * @param AutowireOnlyDependency $dependency
     * @param AutowireOnlyLeaf|null $optionalLeaf
     */
    public function __construct(
        public AutowireOnlyDependency $dependency,
        public ?AutowireOnlyLeaf $optionalLeaf = null,
    ) {}
}

final readonly class AutowireOnlyDependency
{
    /**
     * @param AutowireOnlyLeaf $leaf
     */
    public function __construct(
        public AutowireOnlyLeaf $leaf
    ) {}
}

final class AutowireOnlyLeaf {}

final readonly class AutowireRequiredScalar
{
    /**
     * @param string $label
     */
    public function __construct(
        public string $label
    ) {}
}

final class AutowireThrowingConstructor
{
    public static int $attempts = 0;

    /**
     * @throws RuntimeException
     */
    public function __construct()
    {
        ++self::$attempts;

        throw new RuntimeException('constructor failed');
    }
}

final readonly class AutowireOptionalCycle
{
    /**
     * @param self|null $dependency
     */
    public function __construct(
        public ?self $dependency = null
    ) {}
}
