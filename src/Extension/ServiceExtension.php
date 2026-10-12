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

namespace FastForward\Container\Extension;

use Closure;
use ReflectionFunction;
use ReflectionNamedType;
use Throwable;
use Psr\Container\ContainerInterface;

/**
 * Applies an extension's replacement value while preserving legacy void mutations.
 *
 * This adapter SHALL invoke the extension with the container and previous service.
 * An explicitly declared void return type MUST preserve the previous service after
 * the extension runs. Every other return value SHALL replace it, including null.
 *
 * @internal
 */
final readonly class ServiceExtension
{
    /**
     * @var Closure the normalized extension callable
     */
    private Closure $extension;

    /**
     * @var bool whether the extension explicitly declares a void return type
     */
    private bool $returnsVoid;

    /**
     * Constructs an adapter for the supplied service extension.
     *
     * The callable SHALL be normalized to a closure and its return type inspected
     * once, allowing subsequent invocations to preserve legacy void mutations.
     *
     * @param callable $extension the service extension to normalize and invoke
     */
    public function __construct(callable $extension)
    {
        $this->extension = $extension(...);
        $returnType = (new ReflectionFunction($this->extension))->getReturnType();
        $this->returnsVoid = $returnType instanceof ReflectionNamedType && 'void' === $returnType->getName();
    }

    /**
     * Applies the extension to the previous service value.
     *
     * Explicitly void extensions SHALL preserve the previous value. Other extensions
     * SHALL return their replacement value, including an explicit null result.
     *
     * @param ContainerInterface $container the container used to resolve extension dependencies
     * @param mixed $previous the previous service value, or null when no factory exists
     *
     * @return mixed the replacement value or the preserved value for a void extension
     *
     * @throws Throwable if the supplied extension fails during invocation
     */
    public function __invoke(ContainerInterface $container, mixed $previous): mixed
    {
        $result = ($this->extension)($container, $previous);

        return $this->returnsVoid ? $previous : $result;
    }
}
