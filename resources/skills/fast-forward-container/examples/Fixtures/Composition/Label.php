<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Composition;

/**
 * A configured implementation of the application contract.
 */
final readonly class Label implements LabelContract
{
    /**
     * @param string $text the displayed text
     */
    public function __construct(
        public string $text
    ) {}

    /**
     * @param string $text the supplied text
     *
     * @return self a configured value
     */
    public static function create(string $text): self
    {
        return new self($text);
    }
}
