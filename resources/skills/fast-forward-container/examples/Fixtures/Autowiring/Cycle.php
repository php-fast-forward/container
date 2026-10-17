<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Autowiring;

/**
 * An optional circular graph still has to report its cycle.
 */
final readonly class Cycle
{
    /**
     * @param self|null $dependency the circular optional dependency
     */
    public function __construct(
        public ?self $dependency = null
    ) {}
}
