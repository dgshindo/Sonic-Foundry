<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

use DateTimeImmutable;
use SonicFoundry\Work\WorkPillar;

final class PillarMemory
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly int $id,
        private readonly int $workId,
        private readonly WorkPillar $pillar,
        private readonly array $data,
        private readonly ?float $confidence,
        private readonly MemoryStatus $status,
        private readonly int $revision,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {
        if ($this->id < 1) {
            throw new \InvalidArgumentException(
                'Memory ID must be greater than zero.'
            );
        }

        if ($this->workId < 1) {
            throw new \InvalidArgumentException(
                'Memory Work ID must be greater than zero.'
            );
        }

        if ($this->revision < 1) {
            throw new \InvalidArgumentException(
                'Memory revision must be greater than zero.'
            );
        }

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

        json_encode(
            $this->data,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function workId(): int
    {
        return $this->workId;
    }

    public function pillar(): WorkPillar
    {
        return $this->pillar;
    }

    /**
     * Return the authoritative pillar-specific memory document.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function schemaVersion(): int
    {
        $version = $this->data['schema_version']
            ?? 1;

        return is_int($version) && $version > 0
            ? $version
            : 1;
    }

    public function value(
        string $key,
        mixed $default = null,
    ): mixed {
        return $this->data[$key]
            ?? $default;
    }

    public function confidence(): ?float
    {
        return $this->confidence;
    }

    public function status(): MemoryStatus
    {
        return $this->status;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function revision(): int
    {
        return $this->revision;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isProposed(): bool
    {
        return $this->status
            === MemoryStatus::Proposed;
    }

    public function isConfirmed(): bool
    {
        return $this->status
            === MemoryStatus::Confirmed;
    }
}