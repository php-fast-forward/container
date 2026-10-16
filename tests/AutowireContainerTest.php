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
    }

    /**
     * @return void
     */
    #[Test]
    public function hasReturnsFalseWhenResolutionFails(): void
    {
        $delegate = $this->prophesize(ContainerInterface::class);
        $delegate->has('unstable')
            ->willReturn(true);
        $delegate->get('unstable')
            ->willThrow(new RuntimeException());

        $container = new AutowireContainer($delegate->reveal());

        self::assertFalse($container->has('unstable'));
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
