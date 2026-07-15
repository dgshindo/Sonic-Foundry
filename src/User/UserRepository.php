<?php
declare(strict_types=1);

namespace SonicFoundry\User;

use DateTimeImmutable;
use PDO;
use RuntimeException;

final class UserRepository
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function findById(int $id): ?User
    {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                google_sub,
                email,
                display_name,
                avatar_url,
                password_hash,
                email_verified_at,
                account_status,
                created_at,
                updated_at
            FROM users
            WHERE id = :id
            LIMIT 1
            '
        );

        $statement->execute([
            'id' => $id,
        ]);

        $row = $statement->fetch();

        return $row
            ? $this->hydrate($row)
            : null;
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                google_sub,
                email,
                display_name,
                avatar_url,
                password_hash,
                email_verified_at,
                account_status,
                created_at,
                updated_at
            FROM users
            WHERE email = :email
            LIMIT 1
            '
        );

        $statement->execute([
            'email' => mb_strtolower(trim($email)),
        ]);

        $row = $statement->fetch();

        return $row
            ? $this->hydrate($row)
            : null;
    }

    public function findByGoogleSub(string $googleSub): ?User
    {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                google_sub,
                email,
                display_name,
                avatar_url,
                password_hash,
                email_verified_at,
                account_status,
                created_at,
                updated_at
            FROM users
            WHERE google_sub = :google_sub
            LIMIT 1
            '
        );

        $statement->execute([
            'google_sub' => trim($googleSub),
        ]);

        $row = $statement->fetch();

        return $row
            ? $this->hydrate($row)
            : null;
    }

    public function createGoogleUser(
        string $googleSub,
        string $email,
        string $displayName,
        ?string $avatarUrl,
        bool $emailVerified,
    ): User {
        $normalizedEmail = mb_strtolower(trim($email));
        $normalizedName = trim($displayName);
        $normalizedGoogleSub = trim($googleSub);

        if ($normalizedGoogleSub === '') {
            throw new \InvalidArgumentException(
                'Google subject identifier cannot be empty.'
            );
        }

        if (!filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                'A valid email address is required.'
            );
        }

        if ($normalizedName === '') {
            throw new \InvalidArgumentException(
                'Display name cannot be empty.'
            );
        }

        $statement = $this->pdo->prepare(
            '
            INSERT INTO users (
                google_sub,
                email,
                display_name,
                avatar_url,
                password_hash,
                email_verified_at,
                account_status
            ) VALUES (
                :google_sub,
                :email,
                :display_name,
                :avatar_url,
                NULL,
                :email_verified_at,
                :account_status
            )
            '
        );

        $statement->execute([
            'google_sub' => $normalizedGoogleSub,
            'email' => $normalizedEmail,
            'display_name' => $normalizedName,
            'avatar_url' => $avatarUrl,
            'email_verified_at' => $emailVerified
                ? (new DateTimeImmutable())->format('Y-m-d H:i:s')
                : null,
            'account_status' => 'active',
        ]);

        $userId = (int) $this->pdo->lastInsertId();

        $user = $this->findById($userId);

        if (!$user) {
            throw new RuntimeException(
                'The user was created but could not be reloaded.'
            );
        }

        return $user;
    }

    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            googleSub: $row['google_sub'] !== null
                ? (string) $row['google_sub']
                : null,
            email: (string) $row['email'],
            displayName: (string) $row['display_name'],
            avatarUrl: $row['avatar_url'] !== null
                ? (string) $row['avatar_url']
                : null,
            passwordHash: $row['password_hash'] !== null
                ? (string) $row['password_hash']
                : null,
            emailVerifiedAt: $this->dateOrNull(
                $row['email_verified_at']
            ),
            accountStatus: (string) $row['account_status'],
            createdAt: new DateTimeImmutable(
                (string) $row['created_at']
            ),
            updatedAt: new DateTimeImmutable(
                (string) $row['updated_at']
            ),
        );
    }

    private function dateOrNull(
        mixed $value
    ): ?DateTimeImmutable {
        if ($value === null || $value === '') {
            return null;
        }

        return new DateTimeImmutable(
            (string) $value
        );
    }
}