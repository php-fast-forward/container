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

use FastForward\Container\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RuntimeException::class)]
final class RuntimeExceptionTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function forNonCallableExtensionReturnsProperException(): void
    {
        $exception = RuntimeException::forNonCallableExtension('db.connection', 'array');

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            'Service "db.connection" extension MUST be callable, "array" given.',
            $exception->getMessage()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function forNonPublicMethodReturnsProperException(): void
    {
        $exception = RuntimeException::forNonPublicMethod('My\Service', 'configure');

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            'Method "My\Service::configure" MUST be public to be invoked as a service.',
            $exception->getMessage()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function forInvalidParameterTypeReturnsProperException(): void
    {
        $exception = RuntimeException::forInvalidParameterType('logger');

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            'Parameter "logger" is not a valid type. It MUST be a class name or an interface name.',
            $exception->getMessage()
        );
    }
}
