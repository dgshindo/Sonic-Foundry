<?php
declare(strict_types=1);

namespace SonicFoundry\Auth;

use DateTimeImmutable;
use SonicFoundry\User\User;

final class AuthenticatedUser
{
    public function __construct(
        private User $user,
        private DateTimeImmutable $authenticatedAt,
        private string $authenticationMethod,
    ) {
        if (!$user->isActive()) {
            throw new \DomainException(
                'Only active users may be authenticated.'
            );
        }

        if (!in_array(
            $authenticationMethod,
            ['google', 'password', 'development'],
            true
        )) {
            throw new \InvalidArgumentException(
                'Authentication method is invalid.'
            );
        }
    }

    public function user(): User
    {
        return $this->user;
    }

    public function id(): int
    {
        return $this->user->id();
    }

    public function email(): string
    {
        return $this->user->email();
    }

    public function displayName(): string
    {
        return $this->user->displayName();
    }

    

    public function avatarUrl(): ?string
    {
        return $this->user->avatarUrl();
    }

    public function authenticatedAt(): DateTimeImmutable
    {
        return $this->authenticatedAt;
    }

    public function authenticationMethod(): string
    {
        return $this->authenticationMethod;
    }

    public function authenticatedWithGoogle(): bool
    {
        return $this->authenticationMethod === 'Google';
    }
}