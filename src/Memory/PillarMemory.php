<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

use DateTimeImmutable;
use SonicFoundry\Work\WorkPillar;

final class PillarMemory
{
    /**
     * @param list<string> $themes
     * @param list<string> $keySubjects
     */
    public function __construct(
        private readonly int $id,
        private readonly int $workId,
        private readonly WorkPillar $pillar,
        private readonly ?string $summary,
        private readonly ?string $perspective,
        private readonly ?string $coreTension,
        private readonly ?string $listenerTakeaway,
        private readonly array $themes,
        private readonly array $keySubjects,
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

    public function summary(): ?string
    {
        return $this->summary;
    }

    public function perspective(): ?string
    {
        return $this->perspective;
    }

    public function coreTension(): ?string
    {
        return $this->coreTension;
    }

    public function listenerTakeaway(): ?string
    {
        return $this->listenerTakeaway;
    }

    /**
     * @return list<string>
     */
    public function themes(): array
    {
        return $this->themes;
    }

    /**
     * @return list<string>
     */
    public function keySubjects(): array
    {
        return $this->keySubjects;
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
        return $this->status === MemoryStatus::Proposed;
    }

    public function isConfirmed(): bool
    {
        return $this->status === MemoryStatus::Confirmed;
    }
}