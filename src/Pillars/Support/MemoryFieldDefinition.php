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
        if (
            !preg_match(
                '/^[a-z][a-z0-9_]*$/',
                $this->key
            )
        ) {
            throw new \InvalidArgumentException(
                'Memory field key must use snake_case.'
            );
        }

        if (trim($this->label) === '') {
            throw new \InvalidArgumentException(
                'Memory field label cannot be empty.'
            );
        }

        if (trim($this->emptyMessage) === '') {
            throw new \InvalidArgumentException(
                'Memory field empty message cannot be empty.'
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

    public function type(): MemoryFieldType
    {
        return $this->type;
    }

    public function emptyMessage(): string
    {
        return $this->emptyMessage;
    }
}