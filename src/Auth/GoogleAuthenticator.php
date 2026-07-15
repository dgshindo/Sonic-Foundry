<?php
declare(strict_types=1);

namespace SonicFoundry\Auth;

use Google\Client as GoogleClient;
use RuntimeException;

final class GoogleAuthenticator
{
    public function __construct(
        private GoogleClient $client,
    ) {
    }

    public function verify(string $credential): GoogleIdentity
    {
        $credential = trim($credential);

        if ($credential === '') {
            throw new \InvalidArgumentException(
                'Google credential cannot be empty.'
            );
        }

        $payload = $this->client->verifyIdToken($credential);

        if (!is_array($payload)) {
            throw new RuntimeException(
                'Google identity token could not be verified.'
            );
        }

        $subject = isset($payload['sub'])
            ? (string) $payload['sub']
            : '';

        $email = isset($payload['email'])
            ? (string) $payload['email']
            : '';

        $displayName = isset($payload['name'])
            ? trim((string) $payload['name'])
            : '';

        if ($displayName === '') {
            $displayName = $email;
        }

        $avatarUrl = isset($payload['picture'])
            ? (string) $payload['picture']
            : null;

        $emailVerified = filter_var(
            $payload['email_verified'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        return new GoogleIdentity(
            subject: $subject,
            email: $email,
            displayName: $displayName,
            avatarUrl: $avatarUrl,
            emailVerified: $emailVerified,
        );
    }
}