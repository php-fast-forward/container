<?php

declare(strict_types=1);

namespace FastForward\Container\Tests\Fixtures\Factory;

/**
 * A static factory contract whose implementation must come from the container.
 */
interface MethodFactoryStaticTargetInterface
{
    /**
     * @param string $value the method argument
     *
     * @return string the configured implementation's result
     */
    public static function staticMethod(string $value): string;
}
