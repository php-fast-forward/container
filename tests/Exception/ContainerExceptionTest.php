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

namespace FastForward\Container\Tests\Exception;

use RuntimeException;
use FastForward\Container\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ContainerException::class)]
final class ContainerExceptionTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function forInvalidServiceWillReturnProperException(): void
    {
        $id       = uniqid('service.id');
        $previous = new RuntimeException('Underlying issue', 500);

        $exception = ContainerException::forInvalidService($id, $previous);

        self::assertInstanceOf(ContainerException::class, $exception);
        self::assertInstanceOf(ContainerExceptionInterface::class, $exception);
        self::assertSame(\sprintf('Invalid service "%s".', $id), $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(500, $exception->getCode());
    }
}
