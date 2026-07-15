<?php
declare(strict_types=1);

namespace SonicFoundry\User;

use DateTimeImmutable;

final class User
{
    public function __construct(
        private int $id,
        private ?string $googleSub,
        private string $email,
        private string $displayName,
        private ?string $avatarUrl,
        private ?string $passwordHash,
        private ?DateTimeImmutable $emailVerifiedAt,
        private string $accountStatus,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        if ($id < 1) {
            throw new \InvalidArgumentException(
                'User ID must be greater than zero.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                'User email address is invalid.'
            );
        }

        if ($displayName === '') {
            throw new \InvalidArgumentException(
                'Display name cannot be empty.'
            );
        }

        if (!in_array(
            $accountStatus,
            ['active', 'suspended', 'deleted'],
            true
        )) {
            throw new \InvalidArgumentException(
                'User account status is invalid.'
            );
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function googleSub(): ?string
    {
        return $this->googleSub;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function passwordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function emailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function accountStatus(): string
    {
        return $this->accountStatus;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasGoogleIdentity(): bool
    {
        return $this->googleSub !== null;
    }

    public function hasPassword(): bool
    {
        return $this->passwordHash !== null;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function isActive(): bool
    {
        return $this->accountStatus === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->accountStatus === 'suspended';
    }

    public function isDeleted(): bool
    {
        return $this->accountStatus === 'deleted';
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->displayName))[0];
    }
}