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

use DI\Container as PHPDIContainer;
use FastForward\Config\ConfigInterface;
use FastForward\Container\AggregateContainer;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Factory\ContainerFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(ContainerFactory::class)]
#[UsesClass(PHPDIContainer::class)]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(NotFoundException::class)]
final class ContainerFactoryTest extends TestCase
{
    use ProphecyTrait;

    private ConfigInterface|ObjectProphecy $config;

    private ContainerFactory $factory;

    protected function setUp(): void
    {
        $this->config  = $this->prophesize(ConfigInterface::class);
        $this->factory = new ContainerFactory($this->config->reveal());
    }

    #[Test]
    public function testFactoryCreatesAggregateContainerFromArrayDependencies(): void
    {
        $key      = uniqid('svc_', true);
        $instance = new \stdClass();

        $this->config->get('dependencies', [])->willReturn([
            $key => static fn () => $instance,
        ]);
        $this->config->has($key)->willReturn(false);

        $container = ($this->factory)();

        self::assertInstanceOf(AggregateContainer::class, $container);
        self::assertTrue($container->has($key));
        self::assertSame($instance, $container->get($key));
    }

    #[Test]
    public function testFactoryCreatesAggregateContainerFromConfigInterfaceDependencies(): void
    {
        $key      = uniqid('svc_', true);
        $instance = new \stdClass();

        $depConfig = $this->prophesize(ConfigInterface::class);
        $depConfig->toArray()->willReturn([$key => static fn () => $instance]);

        $this->config->get('dependencies', [])->willReturn($depConfig->reveal());
        $this->config->has($key)->willReturn(false);

        $container = ($this->factory)();

        self::assertInstanceOf(AggregateContainer::class, $container);
        self::assertTrue($container->has($key));
        self::assertSame($instance, $container->get($key));
    }

    #[Test]
    public function testFactoryCanComposeWithAdditionalContainer(): void
    {
        $key   = uniqid('ext_', true);
        $value = uniqid('val_', true);

        $external = $this->prophesize(ContainerInterface::class);
        $external->has($key)->willReturn(true);
        $external->get($key)->willReturn($value);

        $this->config->get('dependencies', [])->willReturn([]);
        $this->config->has($key)->willReturn(false);

        $container = ($this->factory)($external->reveal());

        self::assertInstanceOf(AggregateContainer::class, $container);
        self::assertTrue($container->has($key));
        self::assertSame($value, $container->get($key));
    }
}
