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
use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\ServiceProviderContainer;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(ServiceProviderContainer::class)]
#[UsesClass(NotFoundException::class)]
#[UsesClass(ContainerException::class)]
final class ServiceProviderContainerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $provider;

    private ServiceProviderContainer $container;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->provider  = $this->prophesize(ServiceProviderInterface::class);

        $this->provider->getFactories()
            ->willReturn([]);
        $this->provider->getExtensions()
            ->willReturn([]);

        $this->container = new ServiceProviderContainer($this->provider->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function hasReturnsTrueForCachedService(): void
    {
        $service = new stdClass();

        $this->provider->getFactories()
            ->willReturn([
                'foo' => static fn(): stdClass => $service,
            ]);

        $this->container->get('foo');
        self::assertTrue($this->container->has('foo'));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasReturnsTrueForExistingFactory(): void
    {
        $this->provider->getFactories()
            ->willReturn([
                'bar' => static fn(): stdClass => new stdClass(),
            ]);

        self::assertTrue($this->container->has('bar'));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasReturnsFalseForUnknownId(): void
    {
        $this->provider->getFactories()
            ->willReturn([]);

        self::assertFalse($this->container->has('unknown'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getReturnsServiceFromFactory(): void
    {
        $expected = new stdClass();

        $this->provider->getFactories()
            ->willReturn([
                'my.service' => static fn(): stdClass => $expected,
            ]);
        $this->provider->getExtensions()
            ->willReturn([]);

        $actual = $this->container->get('my.service');
        self::assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    #[Test]
    public function getAppliesExtensionIfAvailable(): void
    {
        $target = new stdClass();

        $factory   = static fn(ContainerInterface $c): stdClass => $target;
        $extension = static function (ContainerInterface $c, object $service): void {
            $service->extended = true;
        };

        $this->provider->getFactories()
            ->willReturn([
                'foo' => $factory,
            ]);
        $this->provider->getExtensions()
            ->willReturn([
                'foo' => $extension,
            ]);

        $service = $this->container->get('foo');

        self::assertSame($target, $service);
        self::assertTrue($service->extended);
    }

    /**
     * @return void
     */
    #[Test]
    public function getThrowsNotFoundExceptionForMissingFactory(): void
    {
        $this->provider->getFactories()
            ->willReturn([]);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Service "invalid" not found.');

        $this->container->get('invalid');
    }

    /**
     * @return void
     */
    #[Test]
    public function getWrapsContainerException(): void
    {
        $this->provider->getFactories()
            ->willReturn([
                'fail.service' => static function (): never {
                    throw new class ('fail') extends RuntimeException implements ContainerExceptionInterface {};
                },
            ]);
        $this->provider->getExtensions()
            ->willReturn([]);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Invalid service "fail.service".');

        $this->container->get('fail.service');
    }

    /**
     * @return void
     */
    #[Test]
    public function getWillReturnFromCacheWhenSericeAlreadyResolved(): void
    {
        $service = new stdClass();

        $this->provider->getFactories()
            ->willReturn([
                'foo' => static fn(): stdClass => $service,
            ])->shouldBeCalledOnce();

        $this->container->get('foo');

        self::assertSame($service, $this->container->get('foo'));
    }

    /**
     * @return void
     */
    #[Test]
    public function applyServiceExtensionByClassName(): void
    {
        $service = new class {
            public bool $extended = false;
        };

        $extension = static function (ContainerInterface $c, object $service): void {
            $service->extended = true;
        };

        $this->provider->getFactories()
            ->willReturn([
                'service.id' => static fn(): object => $service,
            ]);
        $this->provider->getExtensions()
            ->willReturn([
                $service::class => $extension,
            ]);

        $resolved = $this->container->get('service.id');

        self::assertTrue($resolved->extended);
    }

    /**
     * @return void
     */
    #[Test]
    public function applyServiceExtensionByIdAndClass(): void
    {
        $service = new class {
            public array $calls = [];
        };

        $byIdExtension = static function (ContainerInterface $c, object $service): void {
            $service->calls[] = 'id';
        };

        $byClassExtension = static function (ContainerInterface $c, object $service): void {
            $service->calls[] = 'class';
        };

        $this->provider->getFactories()
            ->willReturn([
                'dual' => static fn(): object => $service,
            ]);
        $this->provider->getExtensions()
            ->willReturn([
                'dual'               => $byIdExtension,
                $service::class => $byClassExtension,
            ]);

        $resolved = $this->container->get('dual');

        self::assertSame(['id', 'class'], $resolved->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function applyServiceIgnoresNonCallableExtensions(): void
    {
        $service = new stdClass();

        $this->provider->getFactories()
            ->willReturn([
                'not.callable' => static fn(): stdClass => $service,
            ]);
        $this->provider->getExtensions()
            ->willReturn([
                'not.callable'       => 'not_a_function',
                $service::class => 123,
            ]);

        $resolved = $this->container->get('not.callable');

        self::assertSame($service, $resolved); // Should still return the service
        self::assertObjectNotHasProperty('extended', $resolved); // No extension applied
    }
}
