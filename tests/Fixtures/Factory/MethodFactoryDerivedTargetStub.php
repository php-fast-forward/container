<?php

declare(strict_types=1);

namespace FastForward\Container\Tests\Fixtures\Factory;

/**
 * A configured target exposing methods beyond the class-name binding's contract.
 */
final class MethodFactoryDerivedTargetStub extends MethodFactoryBaseTargetStub
{
    /**
     * @return string the configured public override
     */
    public function overriddenMethod(): string
    {
        return 'configured override';
    }

    /**
     * @return string the result of a method introduced by the configured target
     */
    public function addedMethod(): string
    {
        return 'configured added';
    }
}
