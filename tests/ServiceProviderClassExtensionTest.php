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
use FastForward\Container\Extension\ServiceExtension;
use FastForward\Container\Factory\AliasFactory;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(ServiceProviderContainer::class)]
#[UsesClass(ArrayServiceProvider::class)]
#[UsesClass(ServiceExtension::class)]
#[UsesClass(AliasFactory::class)]
final class ServiceProviderClassExtensionTest extends TestCase
{
    /**
     * @param bool $classFirst whether the class entry is resolved before the named entry
     *
     * @return void
     */
    #[Test]
    #[DataProvider('lookupOrders')]
    public function distinctObjectsReceiveTheirClassExtensionInEitherOrder(bool $classFirst): void
    {
        $classService = (object) [
            'extensions' => 0,
        ];
        $namedService = (object) [
            'extensions' => 0,
        ];
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => $classService,
            'named' => static fn(): stdClass => $namedService,
        ], [
            stdClass::class => static function (ContainerInterface $container, stdClass $service): void {
                ++$service->extensions;
            },
        ]));

        foreach ($classFirst ? [stdClass::class, 'named'] : ['named', stdClass::class] as $id) {
            $container->get($id);
        }

        self::assertSame($classService, $container->get(stdClass::class));
        self::assertSame($namedService, $container->get('named'));
        self::assertNotSame($classService, $namedService);
        self::assertSame(1, $classService->extensions);
        self::assertSame(1, $namedService->extensions);
    }

    /**
     * @return iterable<string, array{bool}> the two independent entry lookup orders
     */
    public static function lookupOrders(): iterable
    {
        yield 'class first' => [true];
        yield 'named first' => [false];
    }

    /**
     * @param string $firstId the first entry requested in the alias chain
     *
     * @return void
     */
    #[Test]
    #[DataProvider('aliasLookups')]
    public function aliasesOfTheCachedClassObjectDoNotRepeatItsExtension(string $firstId): void
    {
        $service = (object) [
            'extensions' => 0,
        ];
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => $service,
            'alias' => new AliasFactory(stdClass::class),
            'alias.chain' => new AliasFactory('alias'),
        ], [
            stdClass::class => static function (ContainerInterface $container, stdClass $service): stdClass {
                ++$service->extensions;

                return $service;
            },
        ]));

        self::assertSame($service, $container->get($firstId));
        self::assertSame($service, $container->get(stdClass::class));
        self::assertSame($service, $container->get('alias'));
        self::assertSame($service, $container->get('alias.chain'));
        self::assertSame(1, $service->extensions);
    }

    /**
     * @return iterable<string, array{string}> direct and nested aliases of a class entry
     */
    public static function aliasLookups(): iterable
    {
        yield 'class first' => [stdClass::class];
        yield 'alias first' => ['alias'];
        yield 'nested alias first' => ['alias.chain'];
    }

    /**
     * @param bool $classFirst whether the distinct class entry is resolved before the named alias
     * @param bool $replace whether the class extension replaces the service with a new object
     *
     * @return void
     */
    #[Test]
    #[DataProvider('namedAliasOrders')]
    public function namedAliasesRetainTheExtendedObject(bool $classFirst, bool $replace): void
    {
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => (object) [
                'extensions' => 0,
            ],
            'named' => static fn(): stdClass => (object) [
                'extensions' => 0,
            ],
            'alias' => new AliasFactory('named'),
            'alias.chain' => new AliasFactory('alias'),
        ], [
            stdClass::class => static function (ContainerInterface $container, stdClass $service) use (
                $replace
            ): stdClass {
                ++$service->extensions;

                return $replace ? clone $service : $service;
            },
        ]));

        if ($classFirst) {
            $container->get(stdClass::class);
        }

        $alias = $container->get('alias.chain');
        $named = $container->get('named');
        $class = $container->get(stdClass::class);

        self::assertSame($named, $alias);
        self::assertSame($named, $container->get('alias'));
        self::assertSame($named, $container->get('alias.chain'));
        self::assertNotSame($class, $named);
        self::assertSame(1, $class->extensions);
        self::assertSame(1, $named->extensions);
    }

    /**
     * @return iterable<string, array{bool, bool}> lookup orders for mutating and replacing class extensions
     */
    public static function namedAliasOrders(): iterable
    {
        yield 'class first, mutation' => [true, false];
        yield 'named alias first, mutation' => [false, false];
        yield 'class first, replacement' => [true, true];
        yield 'named alias first, replacement' => [false, true];
    }

    /**
     * @return void
     */
    #[Test]
    public function distinctObjectCanBeReplacedAfterTheClassEntryHasBeenCached(): void
    {
        $container = new ServiceProviderContainer(new ArrayServiceProvider([
            stdClass::class => static fn(): stdClass => (object) [
                'name' => 'class',
            ],
            'named' => static fn(): stdClass => (object) [
                'name' => 'named',
            ],
        ], [
            stdClass::class => static fn(ContainerInterface $container, stdClass $service): string => $service->name,
        ]));

        self::assertSame('class', $container->get(stdClass::class));
        self::assertSame('named', $container->get('named'));
        self::assertSame('named', $container->get('named'));
    }
}
