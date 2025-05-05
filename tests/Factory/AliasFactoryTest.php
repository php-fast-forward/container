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

use FastForward\Container\Factory\AliasFactory;
use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testInvokeResolvesAliasedServiceFromContainer(): void
    {
        $service = new \stdClass();
        $alias   = 'my.service';

        $container = $this->prophesize(ContainerInterface::class);
        $container->get($alias)->willReturn($service)->shouldBeCalled();

        $factory = new AliasFactory($alias);
        $result  = $factory($container->reveal());

        self::assertSame($service, $result);
    }

    public function testGetReturnsSameInstanceForSameAlias(): void
    {
        $a1 = AliasFactory::get('foo');
        $a2 = AliasFactory::get('foo');

        self::assertInstanceOf(AliasFactory::class, $a1);
        self::assertSame($a1, $a2);
    }

    public function testGetReturnsNewInstanceForDifferentAliases(): void
    {
        $a1 = AliasFactory::get('foo');
        $a2 = AliasFactory::get('bar');

        self::assertInstanceOf(AliasFactory::class, $a1);
        self::assertInstanceOf(AliasFactory::class, $a2);
        self::assertNotSame($a1, $a2);
    }
}
