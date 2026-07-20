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