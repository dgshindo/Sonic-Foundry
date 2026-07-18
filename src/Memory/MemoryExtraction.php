<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

final class MemoryExtraction
{
    /**
     * @param list<string> $themes
     * @param list<string> $keySubjects
     */
    public function __construct(
        private readonly ?string $summary,
        private readonly ?string $perspective,
        private readonly ?string $coreTension,
        private readonly ?string $listenerTakeaway,
        private readonly array $themes,
        private readonly array $keySubjects,
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
    }

    public function summary(): ?string
    {
        return $this->normalizeText(
            $this->summary
        );
    }

    public function perspective(): ?string
    {
        return $this->normalizeText(
            $this->perspective
        );
    }

    public function coreTension(): ?string
    {
        return $this->normalizeText(
            $this->coreTension
        );
    }

    public function listenerTakeaway(): ?string
    {
        return $this->normalizeText(
            $this->listenerTakeaway
        );
    }

    /**
     * @return list<string>
     */
    public function themes(): array
    {
        return $this->normalizeList(
            $this->themes
        );
    }

    /**
     * @return list<string>
     */
    public function keySubjects(): array
    {
        return $this->normalizeList(
            $this->keySubjects
        );
    }

    public function confidence(): ?float
    {
        return $this->confidence;
    }

    public function isEmpty(): bool
    {
        return (
            $this->summary() === null
            && $this->perspective() === null
            && $this->coreTension() === null
            && $this->listenerTakeaway() === null
            && $this->themes() === []
            && $this->keySubjects() === []
        );
    }

    private function normalizeText(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== ''
            ? $normalized
            : null;
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function normalizeList(
        array $values,
    ): array {
        $normalized = [];

        foreach ($values as $value) {
            $value = trim($value);

            if ($value === '') {
                continue;
            }

            $key = mb_strtolower($value);

            if (isset($normalized[$key])) {
                continue;
            }

            $normalized[$key] = $value;
        }

        return array_values($normalized);
    }
}