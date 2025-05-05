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

use FastForward\Container\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RuntimeException::class)]
final class RuntimeExceptionTest extends TestCase
{
    public function testForNonCallableExtensionReturnsProperException(): void
    {
        $exception = RuntimeException::forNonCallableExtension('db.connection', 'array');

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            'Service "db.connection" extension MUST be callable, "array" given.',
            $exception->getMessage()
        );
    }

    public function testForNonPublicMethodReturnsProperException(): void
    {
        $exception = RuntimeException::forNonPublicMethod('My\Service', 'configure');

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            'Method "My\Service::configure" MUST be public to be invoked as a service.',
            $exception->getMessage()
        );
    }
}
