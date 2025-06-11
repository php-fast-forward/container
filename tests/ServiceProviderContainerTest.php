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

namespace FastForward\Container\Tests;

use FastForward\Container\Exception\ContainerException;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\ServiceProviderContainer;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
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

    protected function setUp(): void
    {
        $this->provider  = $this->prophesize(ServiceProviderInterface::class);

        $this->provider->getFactories()->willReturn([]);
        $this->provider->getExtensions()->willReturn([]);

        $this->container = new ServiceProviderContainer($this->provider->reveal());
    }

    public function testHasReturnsTrueForCachedService(): void
    {
        $service = new \stdClass();

        $this->provider->getFactories()->willReturn(['foo' => static fn () => $service]);

        $this->container->get('foo');
        self::assertTrue($this->container->has('foo'));
    }

    public function testHasReturnsTrueForExistingFactory(): void
    {
        $this->provider->getFactories()->willReturn(['bar' => static fn () => new \stdClass()]);

        self::assertTrue($this->container->has('bar'));
    }

    public function testHasReturnsFalseForUnknownId(): void
    {
        $this->provider->getFactories()->willReturn([]);

        self::assertFalse($this->container->has('unknown'));
    }

    public function testGetReturnsServiceFromFactory(): void
    {
        $expected = new \stdClass();

        $this->provider->getFactories()->willReturn(['my.service' => static fn () => $expected]);
        $this->provider->getExtensions()->willReturn([]);

        $actual = $this->container->get('my.service');
        self::assertSame($expected, $actual);
    }

    public function testGetAppliesExtensionIfAvailable(): void
    {
        $target = new \stdClass();

        $factory   = static fn (ContainerInterface $c) => $target;
        $extension = static function (ContainerInterface $c, object $service): void {
            $service->extended = true;
        };

        $this->provider->getFactories()->willReturn(['foo' => $factory]);
        $this->provider->getExtensions()->willReturn(['foo' => $extension]);

        $service = $this->container->get('foo');

        self::assertSame($target, $service);
        self::assertTrue($service->extended);
    }

    public function testGetThrowsNotFoundExceptionForMissingFactory(): void
    {
        $this->provider->getFactories()->willReturn([]);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Service "invalid" not found.');

        $this->container->get('invalid');
    }

    public function testGetWrapsContainerException(): void
    {
        $this->provider->getFactories()->willReturn([
            'fail.service' => static function (): void {
                throw new class('fail') extends \RuntimeException implements ContainerExceptionInterface {};
            },
        ]);
        $this->provider->getExtensions()->willReturn([]);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Invalid service "fail.service".');

        $this->container->get('fail.service');
    }

    public function testGetWillReturnFromCacheWhenSericeAlreadyResolved(): void
    {
        $service = new \stdClass();

        $this->provider->getFactories()->willReturn(['foo' => static fn () => $service])->shouldBeCalledOnce();

        $this->container->get('foo');

        self::assertSame($service, $this->container->get('foo'));
    }

    public function testApplyServiceExtensionByClassName(): void
    {
        $service = new class() {
            public bool $extended = false;
        };

        $extension = static function (ContainerInterface $c, object $service): void {
            $service->extended = true;
        };

        $this->provider->getFactories()->willReturn(['service.id' => static fn () => $service]);
        $this->provider->getExtensions()->willReturn([get_class($service) => $extension]);

        $resolved = $this->container->get('service.id');

        self::assertTrue($resolved->extended);
    }

    public function testApplyServiceExtensionByIdAndClass(): void
    {
        $service = new class() {
            public array $calls = [];
        };

        $byIdExtension = static function (ContainerInterface $c, object $service): void {
            $service->calls[] = 'id';
        };

        $byClassExtension = static function (ContainerInterface $c, object $service): void {
            $service->calls[] = 'class';
        };

        $this->provider->getFactories()->willReturn(['dual' => static fn () => $service]);
        $this->provider->getExtensions()->willReturn([
            'dual' => $byIdExtension,
            get_class($service) => $byClassExtension,
        ]);

        $resolved = $this->container->get('dual');

        self::assertSame(['id', 'class'], $resolved->calls);
    }

    public function testApplyServiceIgnoresNonCallableExtensions(): void
    {
        $service = new \stdClass();

        $this->provider->getFactories()->willReturn(['not.callable' => static fn () => $service]);
        $this->provider->getExtensions()->willReturn([
            'not.callable' => 'not_a_function',
            get_class($service) => 123,
        ]);

        $resolved = $this->container->get('not.callable');

        self::assertSame($service, $resolved); // Should still return the service
        self::assertObjectNotHasProperty('extended', $resolved); // No extension applied
    }
}
