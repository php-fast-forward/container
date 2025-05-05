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

use FastForward\Container\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(InvalidArgumentException::class)]
final class InvalidArgumentExceptionTest extends TestCase
{
    public function testForUnsupportedInitializerReturnsExceptionWithCorrectMessage(): void
    {
        $input     = ['not' => 'valid'];
        $exception = InvalidArgumentException::forUnsupportedInitializer($input);

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame('Unsupported initializer type: array', $exception->getMessage());
    }

    public function testForUnsupportedInitializerWithObject(): void
    {
        $input     = new \stdClass();
        $exception = InvalidArgumentException::forUnsupportedInitializer($input);

        self::assertSame('Unsupported initializer type: stdClass', $exception->getMessage());
    }

    public function testForUnsupportedInitializerWithScalar(): void
    {
        $exception = InvalidArgumentException::forUnsupportedInitializer(42);
        self::assertSame('Unsupported initializer type: int', $exception->getMessage());
    }
}
