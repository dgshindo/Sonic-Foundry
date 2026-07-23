<?php
declare(strict_types=1);

namespace SonicFoundry\Artifact;

use DateTimeImmutable;

final class CreativeArtifact
{
    public function __construct(
        private int $id,
        private int $workId,
        private CreativeArtifactType $type,
        private string $title,
        private string $content,
        private int $revision,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        if ($this->id < 1) {
            throw new \InvalidArgumentException(
                'Artifact ID must be positive.'
            );
        }

        if ($this->workId < 1) {
            throw new \InvalidArgumentException(
                'Artifact Work ID must be positive.'
            );
        }

        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Artifact title cannot be empty.'
            );
        }

        if (trim($this->content) === '') {
            throw new \InvalidArgumentException(
                'Artifact content cannot be empty.'
            );
        }

        if ($this->revision < 1) {
            throw new \InvalidArgumentException(
                'Artifact revision must be positive.'
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

    public function type(): CreativeArtifactType
    {
        return $this->type;
    }

    public function typeLabel(): string
    {
        return $this->type->label();
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
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
}