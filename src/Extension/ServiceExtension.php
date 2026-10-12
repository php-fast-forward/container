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
use Psr\Container\ContainerInterface;

/**
 * Applies an extension's replacement value while preserving legacy void mutations.
 *
 * @internal
 */
final readonly class ServiceExtension
{
    private Closure $extension;

    private bool $returnsVoid;

    /**
     * @param callable $extension
     */
    public function __construct(callable $extension)
    {
        $this->extension = $extension(...);
        $returnType = (new ReflectionFunction($this->extension))->getReturnType();
        $this->returnsVoid = $returnType instanceof ReflectionNamedType && 'void' === $returnType->getName();
    }

    /**
     * @param ContainerInterface $container
     * @param mixed $previous
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $container, mixed $previous): mixed
    {
        $result = ($this->extension)($container, $previous);

        return $this->returnsVoid ? $previous : $result;
    }
}
