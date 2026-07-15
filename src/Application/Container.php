<?php
declare(strict_types=1);

namespace SonicFoundry\Application;

use PDO;
use SonicFoundry\Auth\Auth;
use SonicFoundry\Database\Connection;
use SonicFoundry\User\UserRepository;

final class Container
{
    private ?PDO $database = null;

    private ?UserRepository $userRepository = null;

    private ?Auth $auth = null;

    public function database(): PDO
    {
        if (!$this->database instanceof PDO) {
            $this->database = Connection::get();
        }

        return $this->database;
    }

    public function users(): UserRepository
    {
        if (!$this->userRepository instanceof UserRepository) {
            $this->userRepository = new UserRepository(
                $this->database()
            );
        }

        return $this->userRepository;
    }

    public function auth(): Auth
    {
        if (!$this->auth instanceof Auth) {
            $this->auth = new Auth(
                $this->users()
            );
        }

        return $this->auth;
    }
}