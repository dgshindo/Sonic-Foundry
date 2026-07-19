<?php
declare(strict_types=1);

namespace SonicFoundry\Progress;

use DateTimeImmutable;
use SonicFoundry\Work\WorkPillar;

final class PillarProgress
{
    /**
     * @param list<ProgressCriterion> $criteria
     */
    public function __construct(
        private readonly int $id,
        private readonly int $workId,
        private readonly WorkPillar $pillar,
        private readonly ProgressStatus $status,
        private readonly int $readinessScore,
        private readonly bool $ready,
        private readonly array $criteria,
        private readonly ?string $recommendation,
        private readonly int $revision,
        private readonly DateTimeImmutable $evaluatedAt,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {
        if ($this->id < 1) {
            throw new \InvalidArgumentException(
                'Progress ID must be greater than zero.'
            );
        }

        if ($this->workId < 1) {
            throw new \InvalidArgumentException(
                'Progress Work ID must be greater than zero.'
            );
        }

        if (
            $this->readinessScore < 0
            || $this->readinessScore > 100
        ) {
            throw new \InvalidArgumentException(
                'Readiness score must be between 0 and 100.'
            );
        }

        if ($this->revision < 1) {
            throw new \InvalidArgumentException(
                'Progress revision must be greater than zero.'
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

    public function status(): ProgressStatus
    {
        return $this->status;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function readinessScore(): int
    {
        return $this->readinessScore;
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    /**
     * @return list<ProgressCriterion>
     */
    public function criteria(): array
    {
        return $this->criteria;
    }

    public function recommendation(): ?string
    {
        return $this->recommendation;
    }

    public function revision(): int
    {
        return $this->revision;
    }

    public function evaluatedAt(): DateTimeImmutable
    {
        return $this->evaluatedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}