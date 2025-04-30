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

use FastForward\Container\Factory\InvokableFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(InvokableFactory::class)]
final class InvokableFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeInstantiatesClassWithoutArguments(): void
    {
        $factory   = new InvokableFactory(\stdClass::class);
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $result = $factory($container);

        self::assertInstanceOf(\stdClass::class, $result);
    }

    public function testInvokeInstantiatesClassWithArguments(): void
    {
        $factory   = new InvokableFactory(\DateTimeImmutable::class, ['2024-01-01']);
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $result = $factory($container);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2024-01-01', $result->format('Y-m-d'));
    }
}
