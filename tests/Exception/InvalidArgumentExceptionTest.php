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

use stdClass;
use FastForward\Container\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(InvalidArgumentException::class)]
final class InvalidArgumentExceptionTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function forUnsupportedInitializerReturnsExceptionWithCorrectMessage(): void
    {
        $input     = [
            'not' => 'valid',
        ];
        $exception = InvalidArgumentException::forUnsupportedInitializer($input);

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame('Unsupported initializer type: array', $exception->getMessage());
    }

    /**
     * @return void
     */
    #[Test]
    public function forUnsupportedInitializerWithObject(): void
    {
        $input     = new stdClass();
        $exception = InvalidArgumentException::forUnsupportedInitializer($input);

        self::assertSame('Unsupported initializer type: stdClass', $exception->getMessage());
    }

    /**
     * @return void
     */
    #[Test]
    public function forUnsupportedInitializerWithScalar(): void
    {
        $exception = InvalidArgumentException::forUnsupportedInitializer(42);
        self::assertSame('Unsupported initializer type: int', $exception->getMessage());
    }
}
