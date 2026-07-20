<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

final class MemoryExtraction
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
        private readonly ?float $confidence,
    ) {
        if (
            $this->confidence !== null
            && (
                $this->confidence < 0.0
                || $this->confidence > 1.0
            )
        ) {
            throw new \InvalidArgumentException(
                'Memory confidence must be between 0 and 1.'
            );
        }

        /*
         * Prove that the document can be persisted as JSON.
         */
        json_encode(
            $this->data,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function confidence(): ?float
    {
        return $this->confidence;
    }

    public function schemaVersion(): int
    {
        $version = $this->data['schema_version']
            ?? 1;

        return is_int($version) && $version > 0
            ? $version
            : 1;
    }

    public function isEmpty(): bool
    {
        foreach ($this->data as $key => $value) {
            if ($key === 'schema_version') {
                continue;
            }

            if ($this->containsMeaningfulValue($value)) {
                return false;
            }
        }

        return true;
    }

    private function containsMeaningfulValue(
        mixed $value,
    ): bool {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsMeaningfulValue($item)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
}