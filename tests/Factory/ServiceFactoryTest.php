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

namespace FastForward\Container\Tests\Factory;

use FastForward\Container\Factory\ServiceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(ServiceFactory::class)]
final class ServiceFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeReturnsSameInstance(): void
    {
        $service = new \stdClass();
        $factory = new ServiceFactory($service);

        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $result    = $factory($container);

        self::assertSame($service, $result);
    }
}
