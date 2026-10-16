<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Autowiring;

use RuntimeException;

/**
 * A dependency with a genuine execution failure.
 */
final class Broken
{
    /**
     * Always fail to model a broken optional integration.
     */
    public function __construct()
    {
        throw new RuntimeException('example constructor failure');
    }
}
