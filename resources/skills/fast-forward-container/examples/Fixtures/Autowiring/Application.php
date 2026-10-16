<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Autowiring;

/**
 * An ordinary optional object graph.
 */
final readonly class Application
{
    /**
     * @param Dependency|null $dependency a constructible optional dependency
     * @param OptionalContract|null $optional an absent optional interface
     */
    public function __construct(
        public ?Dependency $dependency = null,
        public ?OptionalContract $optional = null
    ) {}
}
