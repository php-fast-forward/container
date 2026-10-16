<?php

declare(strict_types=1);

namespace FastForward\Container\Tests\Fixtures\Factory;

/**
 * A class-name binding that may resolve to a more capable implementation.
 */
class MethodFactoryBaseTargetStub
{
    /**
     * @return string the inaccessible base implementation
     */
    protected function overriddenMethod(): string
    {
        return 'base';
    }
}
