<?php
declare(strict_types=1);

namespace SonicFoundry\Work;

use DateTimeImmutable;
use InvalidArgumentException;

final class Work
{
    public function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly string $title,
        private readonly WorkType $type,
        private readonly WorkStatus $status,
        private readonly WorkPillar $currentPillar,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException(
                'Work ID must be greater than zero.'
            );
        }

        if ($userId < 1) {
            throw new InvalidArgumentException(
                'Work owner ID must be greater than zero.'
            );
        }

        if (trim($title) === '') {
            throw new InvalidArgumentException(
                'Work title cannot be empty.'
            );
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function type(): WorkType
    {
        return $this->type;
    }

    public function typeLabel(): string
    {
        return $this->type->label();
    }

    public function status(): WorkStatus
    {
        return $this->status;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function currentPillar(): WorkPillar
    {
        return $this->currentPillar;
    }

    public function currentPillarLabel(): string
    {
        return $this->currentPillar->label();
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function belongsTo(int $userId): bool
    {
        return $this->userId === $userId;
    }
}