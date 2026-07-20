<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Support;

final class ProgressCriterion
{
    public function __construct(
        private string $key,
        private string $label,
        private string $description,
    ) {
        if (
            !preg_match(
                '/^[a-z][a-z0-9_]*$/',
                $this->key
            )
        ) {
            throw new \InvalidArgumentException(
                'Progress criterion key must use snake_case.'
            );
        }

        if (trim($this->label) === '') {
            throw new \InvalidArgumentException(
                'Progress criterion label cannot be empty.'
            );
        }

        if (trim($this->description) === '') {
            throw new \InvalidArgumentException(
                'Progress criterion description cannot be empty.'
            );
        }
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function description(): string
    {
        return $this->description;
    }
}