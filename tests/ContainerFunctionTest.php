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

use FastForward\Config\ConfigInterface;
use FastForward\Config\Container\ConfigContainer;
use FastForward\Container\AggregateContainer;
use FastForward\Container\AutowireContainer;
use FastForward\Container\ContainerInterface;
use FastForward\Container\Exception\InvalidArgumentException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\ServiceProviderContainer;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

use function FastForward\Container\container;

/**
 * @internal
 */
#[CoversFunction('FastForward\Container\container')]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(AutowireContainer::class)]
#[UsesClass(ServiceProviderContainer::class)]
#[UsesClass(InvalidArgumentException::class)]
#[UsesClass(NotFoundException::class)]
final class ContainerFunctionTest extends TestCase
{
    use ProphecyTrait;

    public function testReturnsAutowireContainerWrappingAggregate(): void
    {
        $result = container();

        self::assertInstanceOf(AutowireContainer::class, $result);
    }

    public function testAcceptsPsrContainerAsInitializer(): void
    {
        $psr = $this->prophesize(ContainerInterface::class);
        $psr->has('service')->willReturn(true);
        $psr->get('service')->willReturn($psr->reveal());

        $container = container($psr->reveal());

        self::assertInstanceOf(AutowireContainer::class, $container);
        self::assertSame($psr->reveal(), $container->get('service'));
    }

    public function testAcceptsServiceProviderAsInitializer(): void
    {
        $provider = $this->prophesize(ServiceProviderInterface::class);
        $provider->getFactories()->willReturn([]);
        $provider->getExtensions()->willReturn([]);

        $container = container($provider->reveal());

        self::assertInstanceOf(AutowireContainer::class, $container);
        self::assertInstanceOf(ServiceProviderContainer::class, $container->get(ServiceProviderContainer::class));
    }

    public function testAcceptsConfigInterfaceAsInitializer(): void
    {
        $config = $this->prophesize(ConfigInterface::class);
        $config->has(ContainerInterface::class)->willReturn(true);
        $config->get(ContainerInterface::class)->willReturn([]);

        $container = container($config->reveal());

        self::assertInstanceOf(AutowireContainer::class, $container);
        self::assertInstanceOf(ConfigContainer::class, $container->get(ConfigContainer::class));
    }

    public function testAcceptsInstantiableString(): void
    {
        $container = container(DummyContainer::class);

        self::assertInstanceOf(AutowireContainer::class, $container);
        self::assertInstanceOf(DummyContainer::class, $container->get(DummyContainer::class));
    }

    public function testThrowsForUnsupportedInitializer(): void
    {
        $this->expectException(InvalidArgumentException::class);

        container(uniqid());
    }

    public function testConfigContainerWithNestedInitializers(): void
    {
        $nested = new DummyContainer();

        $config = $this->prophesize(ConfigInterface::class);
        $config->has(ContainerInterface::class)->willReturn(true);
        $config->get(ContainerInterface::class)->willReturn([$nested]);

        $container = container($config->reveal());

        self::assertInstanceOf(DummyContainer::class, $container->get(DummyContainer::class));
    }

    public function testContainerSkipsThrowableThrownByConfigContainer(): void
    {
        $config = $this->prophesize(ConfigInterface::class);
        $config->has(ContainerInterface::class)->willReturn(true);
        $config->get(ContainerInterface::class)->willThrow(new \RuntimeException('unexpected'));

        $container = container($config->reveal());

        self::assertInstanceOf(AutowireContainer::class, $container);
    }
}

final class DummyContainer implements ContainerInterface
{
    public function get(string $id): mixed
    {
        return $this;
    }

    public function has(string $id): bool
    {
        return true;
    }
}
