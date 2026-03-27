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
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArrayServiceProvider::class)]
final class ArrayServiceProviderTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function implementsServiceProviderInterface(): void
    {
        $provider = new ArrayServiceProvider();
        self::assertInstanceOf(ServiceProviderInterface::class, $provider);
    }

    /**
     * @return void
     */
    #[Test]
    public function returnsGivenFactories(): void
    {
        $factory  = static fn(): stdClass => new stdClass();
        $provider = new ArrayServiceProvider([
            'id' => $factory,
        ]);

        $factories = $provider->getFactories();

        self::assertArrayHasKey('id', $factories);
        self::assertSame($factory, $factories['id']);
    }

    /**
     * @return void
     */
    #[Test]
    public function returnsGivenExtensions(): void
    {
        $extension = static fn($c, $p) => $p;
        $provider  = new ArrayServiceProvider([], [
            'id' => $extension,
        ]);

        $extensions = $provider->getExtensions();

        self::assertArrayHasKey('id', $extensions);
        self::assertSame($extension, $extensions['id']);
    }

    /**
     * @return void
     */
    #[Test]
    public function returnsEmptyArraysByDefault(): void
    {
        $provider = new ArrayServiceProvider();

        self::assertSame([], $provider->getFactories());
        self::assertSame([], $provider->getExtensions());
    }
}
