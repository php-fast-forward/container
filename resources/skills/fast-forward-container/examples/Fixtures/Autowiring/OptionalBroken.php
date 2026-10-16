<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Autowiring;

/**
 * A default must not hide Broken's execution failure.
 */
final readonly class OptionalBroken
{
    /**
     * @param Broken|null $dependency the failing optional dependency
     */
    public function __construct(
        public ?Broken $dependency = null
    ) {}
}
