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

namespace FastForward\Container\Tests\ServiceProvider;

use stdClass;
use FastForward\Container\Exception\RuntimeException;
use FastForward\Container\Factory\ServiceFactory;
use FastForward\Container\ServiceProvider\AggregateServiceProvider;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\Container\ServiceProviderContainer;
use FastForward\Container\Extension\ServiceExtension;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(AggregateServiceProvider::class)]
#[UsesClass(ServiceFactory::class)]
#[UsesClass(RuntimeException::class)]
#[UsesClass(ServiceExtension::class)]
#[UsesClass(ArrayServiceProvider::class)]
#[UsesClass(ServiceProviderContainer::class)]
final class AggregateServiceProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function getFactoriesMergesAllProvidersAndIncludesSelf(): void
    {
        $factory1 = static fn(): string => 'foo';
        $factory2 = static fn(): string => 'bar';

        $provider1 = $this->prophesize(ServiceProviderInterface::class);
        $provider1->getFactories()
            ->willReturn([
                'service.a' => $factory1,
            ]);

        $provider2 = $this->prophesize(stdClass::class);
        $provider2->willImplement(ServiceProviderInterface::class);
        $provider2->getFactories()
            ->willReturn([
                'service.b' => $factory2,
            ]);

        $aggregate = new AggregateServiceProvider($provider1->reveal(), $provider2->reveal());

        $factories = $aggregate->getFactories();
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $serviceProvider1 = $provider1->reveal();
        $serviceProvider2 = $provider2->reveal();

        self::assertArrayHasKey('service.a', $factories);
        self::assertArrayHasKey('service.b', $factories);
        self::assertArrayHasKey($serviceProvider1::class, $factories);
        self::assertArrayHasKey($serviceProvider2::class, $factories);
        self::assertArrayHasKey(AggregateServiceProvider::class, $factories);
        self::assertInstanceOf(ServiceFactory::class, $factories[AggregateServiceProvider::class]);
        self::assertInstanceOf(ServiceFactory::class, $factories[$serviceProvider1::class]);
        self::assertInstanceOf(ServiceFactory::class, $factories[$serviceProvider2::class]);
        self::assertSame('foo', $factories['service.a']());
        self::assertSame('bar', $factories['service.b']());
        self::assertSame($serviceProvider1, $factories[$serviceProvider1::class]($container));
        self::assertSame($serviceProvider2, $factories[$serviceProvider2::class]($container));
    }

    /**
     * @return void
     */
    #[Test]
    public function getExtensionsMergesAllProvidersAndComposesSameKey(): void
    {
        $provider1 = $this->prophesize(ServiceProviderInterface::class);
        $provider2 = $this->prophesize(ServiceProviderInterface::class);

        $provider1->getExtensions()
            ->willReturn([
                'shared.service' => static fn(ContainerInterface $c, $prev): string => $prev . '.ext1',
            ]);

        $provider2->getExtensions()
            ->willReturn([
                'shared.service' => static fn(ContainerInterface $c, $prev): string => $prev . '.ext2',
            ]);

        $aggregate = new AggregateServiceProvider($provider1->reveal(), $provider2->reveal());

        $extensions = $aggregate->getExtensions();
        $result     = $extensions['shared.service'](
            $this->prophesize(ContainerInterface::class)->reveal(),
            'base'
        );

        self::assertSame('base.ext1.ext2', $result);
    }

    /**
     * @param string $id the opaque service identifier
     *
     * @return void
     */
    #[Test]
    #[DataProvider('numericIdentifiers')]
    public function numericIdentifiersRetainFactoryPrecedenceExtensionsAndCaching(string $id): void
    {
        $calls = 0;
        $extensions = [];
        $first = new ArrayServiceProvider([
            $id => static fn(): string => 'first',
        ], [
            $id => static function (ContainerInterface $container, string $previous) use (&$extensions): string {
                $extensions[] = 'first';

                return $previous . ':first';
            },
        ]);
        $second = new ArrayServiceProvider([
            $id => static function () use (&$calls): string {
                ++$calls;

                return 'second';
            },
        ], [
            $id => static function (ContainerInterface $container, string $previous) use (&$extensions): string {
                $extensions[] = 'second';

                return $previous . ':second';
            },
        ]);
        $container = new ServiceProviderContainer(new AggregateServiceProvider($first, $second));

        self::assertTrue($container->has($id));
        self::assertSame('second:first:second', $container->get($id));
        self::assertSame('second:first:second', $container->get($id));
        self::assertSame(1, $calls);
        self::assertSame(['first', 'second'], $extensions);
    }

    /**
     * @return iterable<string, array{string}> numeric-looking identifiers that PHP may normalize as array keys
     */
    public static function numericIdentifiers(): iterable
    {
        yield 'zero' => ['0'];
        yield 'positive' => ['42'];
        yield 'negative' => ['-42'];
        yield 'leading zeros' => ['0001'];
    }

    /**
     * @return void
     */
    #[Test]
    public function distinctNumericIdentifiersAreNotRenumberedOrIntroduced(): void
    {
        $container = new ServiceProviderContainer(new AggregateServiceProvider(
            new ArrayServiceProvider([
                '42' => static fn(): string => 'forty-two',
            ]),
            new ArrayServiceProvider([
                '84' => static fn(): string => 'eighty-four',
            ]),
        ));

        self::assertSame('forty-two', $container->get('42'));
        self::assertSame('eighty-four', $container->get('84'));
        self::assertFalse($container->has('0'));
        self::assertFalse($container->has('1'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getExtensionsThrowsIfExtensionIsNotCallable(): void
    {
        $provider = $this->prophesize(ServiceProviderInterface::class);
        $provider->getExtensions()
            ->willReturn([
                'invalid' => 'not_a_callable',
            ]);

        $aggregate = new AggregateServiceProvider($provider->reveal());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "invalid" extension MUST be callable, "string" given.');

        $aggregate->getExtensions();
    }
}
