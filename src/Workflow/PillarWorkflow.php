<?php
declare(strict_types=1);

namespace SonicFoundry\Workflow;

use DateTimeImmutable;
use SonicFoundry\Work\WorkPillar;

final class PillarWorkflow
{
    public function __construct(
        private readonly int $id,
        private readonly int $workId,
        private readonly WorkPillar $pillar,
        private readonly WorkflowStatus $status,
        private readonly ?DateTimeImmutable $unlockedAt,
        private readonly ?DateTimeImmutable $completedAt,
        private readonly int $revision,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {
        if ($this->id < 1) {
            throw new \InvalidArgumentException(
                'Workflow ID must be greater than zero.'
            );
        }

        if ($this->workId < 1) {
            throw new \InvalidArgumentException(
                'Workflow Work ID must be greater than zero.'
            );
        }

        if ($this->revision < 1) {
            throw new \InvalidArgumentException(
                'Workflow revision must be greater than zero.'
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

    public function status(): WorkflowStatus
    {
        return $this->status;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function unlockedAt(): ?DateTimeImmutable
    {
        return $this->unlockedAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
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

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    public function isAvailable(): bool
    {
        return $this->status->isAvailable();
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }
}