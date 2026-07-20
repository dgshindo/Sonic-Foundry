<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Support;

final class MemoryFieldDefinition
{
    public function __construct(
        private string $key,
        private string $label,
        private MemoryFieldType $type,
        private string $emptyMessage,
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

    public function type(): MemoryFieldType
    {
        return $this->type;
    }

    public function emptyMessage(): string
    {
        return $this->emptyMessage;
    }
}