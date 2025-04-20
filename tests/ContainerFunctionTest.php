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

use FastForward\Config\ConfigInterface;
use FastForward\Container\AggregateContainer;
use FastForward\Container\Exception\NotFoundException;
use FastForward\Container\Factory\ContainerFactory;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

use function FastForward\Container\container;

/**
 * @internal
 */
#[CoversFunction('FastForward\Container\container')]
#[UsesClass(AggregateContainer::class)]
#[UsesClass(NotFoundException::class)]
#[UsesClass(ContainerFactory::class)]
final class ContainerFunctionTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function testContainerReturnsAggregateContainerWithDependencies(): void
    {
        $key   = uniqid('svc_', true);
        $value = uniqid('val_', true);

        $config = $this->prophesize(ConfigInterface::class);
        $config->get('dependencies', [])->willReturn([
            $key => static fn () => $value,
        ]);
        $config->has($key)->willReturn(false);

        $result = container($config->reveal());

        self::assertInstanceOf(AggregateContainer::class, $result);
        self::assertTrue($result->has($key));
        self::assertSame($value, $result->get($key));
    }

    #[Test]
    public function testContainerCanMergeExternalContainers(): void
    {
        $key = uniqid('ext_', true);
        $val = uniqid('external_', true);

        $config = $this->prophesize(ConfigInterface::class);
        $config->get('dependencies', [])->willReturn([]);
        $config->has($key)->willReturn(false);

        $external = $this->prophesize(ContainerInterface::class);
        $external->has($key)->willReturn(true);
        $external->get($key)->willReturn($val);

        $result = container($config->reveal(), $external->reveal());

        self::assertInstanceOf(AggregateContainer::class, $result);
        self::assertTrue($result->has($key));
        self::assertSame($val, $result->get($key));
    }
}
