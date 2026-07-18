<?php
declare(strict_types=1);

namespace SonicFoundry\AI;

final class Prompt
{
    /**
     * @param array<string, string> $metadata
     */
    public function __construct(
        private readonly string $name,
        private readonly string $content,
        private readonly array $metadata = [],
    ) {
        if (trim($this->name) === '') {
            throw new \InvalidArgumentException(
                'Prompt name cannot be empty.'
            );
        }

        if (trim($this->content) === '') {
            throw new \InvalidArgumentException(
                'Prompt content cannot be empty.'
            );
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function content(): string
    {
        return $this->content;
    }

    /**
     * @return array<string, string>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function metadataValue(
        string $key,
        ?string $default = null,
    ): ?string {
        return $this->metadata[$key]
            ?? $default;
    }

    public function version(): string
    {
        return $this->metadataValue(
            'version',
            '1.0'
        ) ?? '1.0';
    }
}