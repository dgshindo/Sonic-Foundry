<?php
declare(strict_types=1);

namespace SonicFoundry\Auth;

final class Session
{
    private const FLASH_KEY = '_flash';

    private const CSRF_KEY = '_csrf_token';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = isset($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off';

        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => $secure,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
            'use_only_cookies' => true,
        ]);

        self::ageFlashData();
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();

        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(
        string $key,
        mixed $value
    ): void {
        self::start();

        $_SESSION[self::FLASH_KEY]['new'][$key] = $value;
    }

    public static function getFlash(
        string $key,
        mixed $default = null
    ): mixed {
        self::start();

        return $_SESSION[self::FLASH_KEY]['old'][$key]
            ?? $default;
    }

    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $parameters['path'],
                    'domain' => $parameters['domain'],
                    'secure' => $parameters['secure'],
                    'httponly' => $parameters['httponly'],
                    'samesite' => 'Lax',
                ]
            );
        }

        session_destroy();
    }

    private static function ageFlashData(): void
    {
        $flash = $_SESSION[self::FLASH_KEY] ?? [];

        $_SESSION[self::FLASH_KEY] = [
            'old' => $flash['new'] ?? [],
            'new' => [],
        ];
    }

    public static function csrfToken(): string
    {
        self::start();

        $token = $_SESSION[self::CSRF_KEY] ?? null;

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::CSRF_KEY] = $token;
        }

        return $token;
    }

    public static function verifyCsrfToken(
        ?string $submittedToken
    ): bool {
        self::start();

        $storedToken = $_SESSION[self::CSRF_KEY] ?? null;

        if (
            !is_string($storedToken)
            || !is_string($submittedToken)
            || $submittedToken === ''
        ) {
            return false;
        }

        return hash_equals(
            $storedToken,
            $submittedToken
        );
    }
}