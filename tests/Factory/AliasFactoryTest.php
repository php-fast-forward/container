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

namespace FastForward\Container\Tests\Factory;

use stdClass;
use FastForward\Container\Factory\AliasFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(AliasFactory::class)]
final class AliasFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function invokeResolvesAliasedServiceFromContainer(): void
    {
        $service = new stdClass();
        $alias   = 'my.service';

        $container = $this->prophesize(ContainerInterface::class);
        $container->get($alias)
            ->willReturn($service)
            ->shouldBeCalled();

        $factory = new AliasFactory($alias);
        $result  = $factory($container->reveal());

        self::assertSame($service, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function getReturnsSameInstanceForSameAlias(): void
    {
        $a1 = AliasFactory::get('foo');
        $a2 = AliasFactory::get('foo');

        self::assertInstanceOf(AliasFactory::class, $a1);
        self::assertSame($a1, $a2);
    }

    /**
     * @return void
     */
    #[Test]
    public function getReturnsNewInstanceForDifferentAliases(): void
    {
        $a1 = AliasFactory::get('foo');
        $a2 = AliasFactory::get('bar');

        self::assertInstanceOf(AliasFactory::class, $a1);
        self::assertInstanceOf(AliasFactory::class, $a2);
        self::assertNotSame($a1, $a2);
    }
}
