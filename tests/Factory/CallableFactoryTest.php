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

use FastForward\Container\Factory\CallableFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(CallableFactory::class)]
final class CallableFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeExecutesProvidedClosure(): void
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $factory = new CallableFactory(static fn (ContainerInterface $c) => (object) ['resolved' => true]);

        $result = $factory($container);

        self::assertIsObject($result);
        self::assertTrue($result->resolved);
    }

    public function testClosureReceivesContainerAsArgument(): void
    {
        $expected = $this->prophesize(ContainerInterface::class)->reveal();

        $factory = new CallableFactory(static fn (ContainerInterface $container) => $container);

        $actual = $factory($expected);

        self::assertSame($expected, $actual);
    }
}
