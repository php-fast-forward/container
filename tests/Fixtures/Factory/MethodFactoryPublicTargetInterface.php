<?php

declare(strict_types=1);

namespace FastForward\Container\Tests\Fixtures\Factory;

/**
 * An additional public contract implemented by the configured subclass.
 */
interface MethodFactoryPublicTargetInterface
{
    /**
     * @return string the publicly accessible configured result
     */
    public function overriddenMethod(): string;
}
