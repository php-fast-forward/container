<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Autowiring;

/**
 * A known class whose required scalar is not inferable.
 */
final readonly class RequiredScalar
{
    /**
     * @param string $value the required scalar
     */
    public function __construct(
        public string $value
    ) {}
}
