<?php

declare(strict_types=1);

namespace FastForward\Container\SkillExamples\Composition;

/**
 * A configured instance target for MethodFactory.
 */
final readonly class LabelFactory
{
    /**
     * @param string $prefix the configuration supplied by its provider
     */
    public function __construct(
        private string $prefix
    ) {}

    /**
     * @param string $text the label's supplied suffix
     *
     * @return Label the configured label
     */
    public function create(string $text): Label
    {
        return new Label($this->prefix . $text);
    }
}
