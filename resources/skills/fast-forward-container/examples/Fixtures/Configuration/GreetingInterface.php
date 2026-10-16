<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Configuration;

/**
 * A configured application dependency.
 */
interface GreetingInterface
{
    /**
     * @return string the configured greeting
     */
    public function greet(): string;
}
