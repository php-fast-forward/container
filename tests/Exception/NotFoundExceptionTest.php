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

namespace FastForward\Container\Tests\Exception;

use FastForward\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @internal
 */
#[CoversClass(NotFoundException::class)]
final class NotFoundExceptionTest extends TestCase
{
    #[Test]
    public function testForServiceIDReturnsExpectedMessage(): void
    {
        $id = uniqid('service_', true);

        $exception = NotFoundException::forServiceID($id);

        self::assertInstanceOf(NotFoundException::class, $exception);
        self::assertInstanceOf(NotFoundExceptionInterface::class, $exception);
        self::assertSame(\sprintf('Service "%s" not found.', $id), $exception->getMessage());
    }
}
