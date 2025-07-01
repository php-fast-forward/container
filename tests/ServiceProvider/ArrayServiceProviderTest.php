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

namespace FastForward\Container\Tests\ServiceProvider;

use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArrayServiceProvider::class)]
final class ArrayServiceProviderTest extends TestCase
{
    public function testImplementsServiceProviderInterface(): void
    {
        $provider = new ArrayServiceProvider();
        self::assertInstanceOf(ServiceProviderInterface::class, $provider);
    }

    public function testReturnsGivenFactories(): void
    {
        $factory  = static fn () => new \stdClass();
        $provider = new ArrayServiceProvider(['id' => $factory]);

        $factories = $provider->getFactories();

        self::assertArrayHasKey('id', $factories);
        self::assertSame($factory, $factories['id']);
    }

    public function testReturnsGivenExtensions(): void
    {
        $extension = static fn ($c, $p) => $p;
        $provider  = new ArrayServiceProvider([], ['id' => $extension]);

        $extensions = $provider->getExtensions();

        self::assertArrayHasKey('id', $extensions);
        self::assertSame($extension, $extensions['id']);
    }

    public function testReturnsEmptyArraysByDefault(): void
    {
        $provider = new ArrayServiceProvider();

        self::assertSame([], $provider->getFactories());
        self::assertSame([], $provider->getExtensions());
    }
}
