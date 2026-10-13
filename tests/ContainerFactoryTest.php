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
use FastForward\Config\ArrayConfig;
use FastForward\Container\AggregateContainer;
use FastForward\Container\AutowireContainer;
use FastForward\Container\ContainerBuilder;
use FastForward\Container\ContainerFactory;
use FastForward\Container\ContainerInterface;
use FastForward\Container\Exception\InvalidArgumentException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Factory\ServiceFactory;
use FastForward\Container\ServiceProvider\AggregateServiceProvider;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @internal
 */
#[CoversClass(ContainerFactory::class)]
#[UsesClass(ContainerBuilder::class)]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(AutowireContainer::class)]
#[UsesClass(ServiceProviderContainer::class)]
#[UsesClass(ArrayServiceProvider::class)]
#[UsesClass(AggregateServiceProvider::class)]
#[UsesClass(ServiceFactory::class)]
#[UsesClass(NotFoundException::class)]
#[UsesClass(InvalidArgumentException::class)]
final class ContainerFactoryTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function successiveCallsCreateIndependentContainerCaches(): void
    {
        $calls = 0;
        $provider = new ArrayServiceProvider([
            'service' => static function () use (&$calls): stdClass {
                ++$calls;

                return new stdClass();
            },
        ]);
        $first = ContainerFactory::create($provider);
        $second = ContainerFactory::create($provider);

        $firstService = $first->get('service');
        $secondService = $second->get('service');

        self::assertNotSame($first, $second);
        self::assertNotSame($firstService, $secondService);
        self::assertSame($firstService, $first->get('service'));
        self::assertSame($secondService, $second->get('service'));
        self::assertSame(2, $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function providersRetainPrecedenceAndResolveThroughTheAggregate(): void
    {
        $dependency = new stdClass();
        $first = new ArrayServiceProvider([
            'service' => static fn(PsrContainerInterface $container): mixed => $container->get('dependency'),
        ]);
        $second = new ArrayServiceProvider([
            'dependency' => static fn(): stdClass => $dependency,
            'service' => static fn(): stdClass => new stdClass(),
        ]);

        $container = ContainerFactory::create($first, $second);

        self::assertSame($dependency, $container->get('service'));
        self::assertSame($dependency, $container->get('dependency'));
    }

    /**
     * @return void
     */
    #[Test]
    public function acceptsClassStringsAndNestedConfigurationProviders(): void
    {
        $config = new ArrayConfig([
            ContainerInterface::class => [
                new ArrayServiceProvider([
                    'configured' => static fn(): string => 'nested value',
                ]),
            ],
        ]);

        $container = ContainerFactory::create(AggregateServiceProvider::class, $config);

        self::assertInstanceOf(AggregateServiceProvider::class, $container->get(AggregateServiceProvider::class));
        self::assertSame('nested value', $container->get('configured'));
    }

    /**
     * @return void
     */
    #[Test]
    public function invalidNestedInitializerStopsExpansionAndKeepsExplicitSources(): void
    {
        $config = new ArrayConfig([
            ContainerInterface::class => [
                new ArrayServiceProvider([
                    'before_invalid' => static fn(): string => 'kept',
                ]),
                new stdClass(),
                new ArrayServiceProvider([
                    'after_invalid' => static fn(): string => 'skipped',
                ]),
            ],
        ]);
        $explicit = new ArrayServiceProvider([
            'outside_config' => static fn(): string => 'available',
        ]);

        $container = ContainerFactory::create($config, $explicit);

        self::assertSame('kept', $container->get('before_invalid'));
        self::assertSame('available', $container->get('outside_config'));
        self::assertFalse($container->has('after_invalid'));
    }
}
