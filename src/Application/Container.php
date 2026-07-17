<?php
declare(strict_types=1);

namespace SonicFoundry\Application;

use PDO;
use SonicFoundry\Auth\Auth;
use SonicFoundry\Database\Connection;
use SonicFoundry\User\UserRepository;
use Google\Client as GoogleClient;
use SonicFoundry\Auth\GoogleAuthenticator;
use SonicFoundry\Auth\GoogleLoginService;
use SonicFoundry\Auth\PasswordLoginService;
use SonicFoundry\Auth\RegistrationService;
use SonicFoundry\Work\WorkRepository;
use SonicFoundry\Work\WorkService;
use SonicFoundry\Conversation\ConversationRepository;
use SonicFoundry\Conversation\ConversationService;


final class Container
{
    private ?RegistrationService $registrationService = null;
    
    private ?PasswordLoginService $passwordLoginService = null;

    private ?GoogleLoginService $googleLoginService = null;

    private ?GoogleAuthenticator $googleAuthenticator = null;

    private ?PDO $database = null;

    private ?UserRepository $userRepository = null;

    private ?Auth $auth = null;

    private ?WorkRepository $workRepository = null;

    private ?WorkService $workService = null;

    private ?ConversationRepository $conversationRepository = null;

    private ?ConversationService $conversationService = null;

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

    public function googleAuthenticator(): GoogleAuthenticator
    {
        if (!$this->googleAuthenticator instanceof GoogleAuthenticator) {
            $clientId = env('GOOGLE_CLIENT_ID');

            if (!$clientId) {
                throw new \RuntimeException(
                    'GOOGLE_CLIENT_ID is not configured.'
                );
            }

            $googleClient = new GoogleClient([
                'client_id' => $clientId,
            ]);

            $this->googleAuthenticator = new GoogleAuthenticator(
                $googleClient
            );
        }

        return $this->googleAuthenticator;
    }

    public function googleLogin(): GoogleLoginService
    {
        if (!$this->googleLoginService instanceof GoogleLoginService) {
            $this->googleLoginService = new GoogleLoginService(
                googleAuthenticator: $this->googleAuthenticator(),
                users: $this->users(),
                auth: $this->auth(),
            );
        }

        return $this->googleLoginService;
    }

   

    public function registration(): RegistrationService
    {
        if (
            !$this->registrationService
            instanceof RegistrationService
        ) {
            $this->registrationService =
                new RegistrationService(
                    users: $this->users(),
                    auth: $this->auth(),
                );
        }

        return $this->registrationService;
    }

    public function passwordLogin(): PasswordLoginService
    {
        if (
            !$this->passwordLoginService
            instanceof PasswordLoginService
        ) {
            $this->passwordLoginService =
                new PasswordLoginService(
                    users: $this->users(),
                    auth: $this->auth(),
                );
        }

        return $this->passwordLoginService;
    }

    public function works(): WorkRepository
    {
        if (!$this->workRepository instanceof WorkRepository) {
            $this->workRepository = new WorkRepository(
                $this->database()
            );
        }

        return $this->workRepository;
    }

    public function workService(): WorkService
    {
        if (!$this->workService instanceof WorkService) {
            $this->workService = new WorkService(
                $this->works()
            );
        }

        return $this->workService;
    }

    public function conversations(): ConversationRepository
    {
        if (
            !$this->conversationRepository
            instanceof ConversationRepository
        ) {
            $this->conversationRepository =
                new ConversationRepository(
                    $this->database()
                );
        }

        return $this->conversationRepository;
    }

    public function conversationService(): ConversationService
    {
        if (
            !$this->conversationService
            instanceof ConversationService
        ) {
            $this->conversationService =
                new ConversationService(
                    messages: $this->conversations(),
                    works: $this->workService(),
                );
        }

        return $this->conversationService;
    }

}