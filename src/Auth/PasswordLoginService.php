<?php
declare(strict_types=1);

namespace SonicFoundry\Auth;

use SonicFoundry\User\UserRepository;

final class PasswordLoginService
{
    public function __construct(
        private UserRepository $users,
        private Auth $auth,
    ) {
    }

    public function login(
        string $email,
        string $password,
    ): AuthenticatedUser {
        $user = $this->users->findByEmail(
            mb_strtolower(trim($email))
        );

        if (
            !$user ||
            !$user->hasPassword() ||
            !password_verify(
                $password,
                (string) $user->passwordHash()
            )
        ) {
            throw new \DomainException(
                'The email address or password is incorrect.'
            );
        }

        return $this->auth->login(
            user: $user,
            authenticationMethod: 'password',
        );
    }
}