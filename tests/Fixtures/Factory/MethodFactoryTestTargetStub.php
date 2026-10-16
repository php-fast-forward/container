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

namespace FastForward\Container\Tests\Fixtures\Factory;

/**
 * A target for public instance/static methods and visibility checks.
 */
class MethodFactoryTestTargetStub
{
    /**
     * @param MethodFactoryTestDependencyStub|null $dependency
     */
    public function __construct(
        private readonly ?MethodFactoryTestDependencyStub $dependency = null
    ) {
        $this->privateMethod();
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    public function instanceMethod(string $prefix): string
    {
        return $prefix . '-instance';
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public static function staticMethod(string $value): string
    {
        return 'static-' . $value;
    }

    /**
     * @param object $obj
     *
     * @return object
     */
    public function acceptsObject(object $obj): object
    {
        return $obj;
    }

    /**
     * @param string $suffix
     *
     * @return string
     */
    public function usesConstructorArgument(string $suffix): string
    {
        return $this->dependency::class . '-' . $suffix;
    }

    /**
     * @return void
     */
    private function privateMethod(): void {}
}
