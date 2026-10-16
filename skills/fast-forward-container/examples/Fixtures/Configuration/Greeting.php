<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Configuration;

/**
 * A service with a required scalar that needs an explicit factory.
 */
final readonly class Greeting implements GreetingInterface
{
    /**
     * @param string $name the application name
     */
    public function __construct(
        private string $name
    ) {}

    /**
     * @return string the configured greeting
     */
    public function greet(): string
    {
        return 'Hello ' . $this->name;
    }
}
