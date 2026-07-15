<?php
declare(strict_types=1);

namespace SonicFoundry\Auth;

use DateTimeImmutable;
use SonicFoundry\User\User;
use SonicFoundry\User\UserRepository;

final class Auth
{
    private const USER_ID_KEY = 'auth_user_id';
    private const METHOD_KEY = 'auth_method';
    private const AUTHENTICATED_AT_KEY = 'auth_authenticated_at';

    private ?AuthenticatedUser $authenticatedUser = null;

    public function __construct(
        private UserRepository $users,
        ) {
    }

    public function login(
        User $user,
        string $authenticationMethod
    ): AuthenticatedUser {
        if (!$user->isActive()) {
            throw new \DomainException(
                'This account is not permitted to sign in.'
            );
        }

        $authenticatedAt = new DateTimeImmutable();

        Session::regenerate();

        Session::put(
            self::USER_ID_KEY,
            $user->id()
        );

        Session::put(
            self::METHOD_KEY,
            $authenticationMethod
        );

        Session::put(
            self::AUTHENTICATED_AT_KEY,
            $authenticatedAt->format(DATE_ATOM)
        );

        $this->authenticatedUser = new AuthenticatedUser(
            user: $user,
            authenticatedAt: $authenticatedAt,
            authenticationMethod: $authenticationMethod,
        );

        return $this->authenticatedUser;
    }

    public function logout(): void
    {
        $this->authenticatedUser = null;
        Session::destroy();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?AuthenticatedUser
    {
        if ($this->authenticatedUser instanceof AuthenticatedUser) {
            return $this->authenticatedUser;
        }

        $userId = Session::get(self::USER_ID_KEY);
        $method = Session::get(self::METHOD_KEY);
        $authenticatedAt = Session::get(
            self::AUTHENTICATED_AT_KEY
        );

        if (
            !is_int($userId)
            && !ctype_digit((string) $userId)
        ) {
            return null;
        }

        if (
            !is_string($method)
            || !is_string($authenticatedAt)
        ) {
            return null;
        }

        $user = $this->users->findById(
            (int) $userId
        );

        if (!$user || !$user->isActive()) {
            $this->logout();

            return null;
        }

        try {
            $authenticatedAtDate = new DateTimeImmutable(
                $authenticatedAt
            );

            $this->authenticatedUser = new AuthenticatedUser(
                user: $user,
                authenticatedAt: $authenticatedAtDate,
                authenticationMethod: $method,
            );
        } catch (\Throwable) {
            $this->logout();

            return null;
        }

        return $this->authenticatedUser;
    }

    public function requireAuthentication(
        string $loginPath = '/login.php'
    ): AuthenticatedUser {
        $user = $this->user();

        if ($user) {
            return $user;
        }

        Session::flash(
            'auth_error',
            'Please sign in to enter the Workspace.'
        );

        header('Location: ' . $loginPath);
        exit;
    }
}