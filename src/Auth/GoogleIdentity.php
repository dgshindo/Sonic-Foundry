<?php
declare(strict_types=1);

namespace SonicFoundry\Auth;

final class GoogleIdentity
{
    public function __construct(
        private string $subject,
        private string $email,
        private string $displayName,
        private ?string $avatarUrl,
        private bool $emailVerified,
    ) {
        if ($subject === '') {
            throw new \InvalidArgumentException(
                'Google subject identifier cannot be empty.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                'Google identity email is invalid.'
            );
        }

        if ($displayName === '') {
            throw new \InvalidArgumentException(
                'Google identity display name cannot be empty.'
            );
        }
    }

    public function subject(): string
    {
        return $this->subject;
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

    public function emailVerified(): bool
    {
        return $this->emailVerified;
    }
}