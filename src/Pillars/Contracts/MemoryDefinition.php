<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Contracts;

use SonicFoundry\Pillars\Support\MemoryFieldDefinition;

final class MemoryDefinition
{
    /**
     * @param list<MemoryFieldDefinition> $fields
     */
    public function __construct(
        private array $fields,
        private string $extractionPromptPath,
    ) {
        if ($this->fields === []) {
            throw new \InvalidArgumentException(
                'A memory definition requires at least one field.'
            );
        }

        $knownKeys = [];

        foreach ($this->fields as $field) {
            if (
                !$field
                instanceof MemoryFieldDefinition
            ) {
                throw new \InvalidArgumentException(
                    'Every memory field must be a MemoryFieldDefinition.'
                );
            }

            $key = $field->key();

            if (isset($knownKeys[$key])) {
                throw new \InvalidArgumentException(
                    'Memory field keys must be unique.'
                );
            }

            $knownKeys[$key] = true;
        }

        $this->assertValidPromptPath(
            $this->extractionPromptPath
        );
    }

    /**
     * @return list<MemoryFieldDefinition>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function extractionPromptPath(): string
    {
        return $this->extractionPromptPath;
    }

    private function assertValidPromptPath(
        string $promptPath,
    ): void {
        $promptPath = trim(
            $promptPath
        );

        if ($promptPath === '') {
            throw new \InvalidArgumentException(
                'Memory extraction prompt path cannot be empty.'
            );
        }

        if (!str_ends_with($promptPath, '.md')) {
            throw new \InvalidArgumentException(
                'Memory extraction prompt must reference a Markdown file.'
            );
        }

        if (
            str_starts_with($promptPath, '/')
            || str_contains($promptPath, '..')
        ) {
            throw new \InvalidArgumentException(
                'Memory extraction prompt path must be relative and safe.'
            );
        }
    }
}