<?php
declare(strict_types=1);

namespace SonicFoundry\Auth;

use SonicFoundry\User\UserRepository;

final class RegistrationService
{
    public function __construct(
        private UserRepository $users,
        private Auth $auth,
    ) {
    }

    public function register(
        string $displayName,
        string $email,
        string $password,
        string $passwordConfirmation,
    ): AuthenticatedUser {
        $displayName = trim($displayName);
        $email = mb_strtolower(trim($email));

        if ($displayName === '') {
            throw new \DomainException(
                'Enter your name.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException(
                'Enter a valid email address.'
            );
        }

        if (strlen($password) < 12) {
            throw new \DomainException(
                'Password must contain at least 12 characters.'
            );
        }

        if ($password !== $passwordConfirmation) {
            throw new \DomainException(
                'Password confirmation does not match.'
            );
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            throw new \RuntimeException(
                'Password could not be secured.'
            );
        }

        $user = $this->users->createPasswordUser(
            email: $email,
            displayName: $displayName,
            passwordHash: $passwordHash,
        );

        return $this->auth->login(
            user: $user,
            authenticationMethod: 'password',
        );
    }
}