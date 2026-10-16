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
use FastForward\Container\Exception\AutowiringException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

/**
 * @internal
 */
#[CoversClass(AutowiringException::class)]
final class AutowiringExceptionTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function forUnresolvableParameterPreservesDetailsAndCause(): void
    {
        $previous = new RuntimeException('The dependency graph is unavailable.');
        $exception = AutowiringException::forUnresolvableParameter('Service', 'logger', '?Logger', $previous);

        self::assertInstanceOf(ContainerExceptionInterface::class, $exception);
        self::assertSame(
            'Cannot autowire Service::__construct(): parameter $logger (?Logger) requires an explicit service or factory.',
            $exception->getMessage(),
        );
        self::assertSame(0, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }

    /**
     * @return void
     */
    #[Test]
    public function forUnresolvableParameterIdentifiesUntypedArguments(): void
    {
        $exception = AutowiringException::forUnresolvableParameter('Service', 'dependency', null);

        self::assertSame(
            'Cannot autowire Service::__construct(): parameter $dependency (untyped) requires an explicit service or factory.',
            $exception->getMessage(),
        );
        self::assertNull($exception->getPrevious());
    }
}
