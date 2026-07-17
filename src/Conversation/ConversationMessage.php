<?php
declare(strict_types=1);

namespace SonicFoundry\Conversation;

use DateTimeImmutable;
use InvalidArgumentException;
use SonicFoundry\Work\WorkPillar;

final class ConversationMessage
{
    public function __construct(
        private readonly int $id,
        private readonly int $workId,
        private readonly WorkPillar $pillar,
        private readonly MessageRole $role,
        private readonly string $content,
        private readonly DateTimeImmutable $createdAt,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException(
                'Message ID must be greater than zero.'
            );
        }

        if ($workId < 1) {
            throw new InvalidArgumentException(
                'Message Work ID must be greater than zero.'
            );
        }

        if (trim($content) === '') {
            throw new InvalidArgumentException(
                'Message content cannot be empty.'
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

    public function role(): MessageRole
    {
        return $this->role;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isUserMessage(): bool
    {
        return $this->role === MessageRole::User;
    }

    public function isPartnerMessage(): bool
    {
        return $this->role === MessageRole::Partner;
    }
}