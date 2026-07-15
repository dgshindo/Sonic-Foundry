<?php
declare(strict_types=1);

namespace SonicFoundry\Auth;

use SonicFoundry\User\User;
use SonicFoundry\User\UserRepository;

final class GoogleLoginService
{
    public function __construct(
        private GoogleAuthenticator $googleAuthenticator,
        private UserRepository $users,
        private Auth $auth,
    ) {
    }

    public function login(string $credential): AuthenticatedUser
    {
        $identity = $this->googleAuthenticator->verify(
            $credential
        );

        $user = $this->users->findByGoogleSub(
            $identity->subject()
        );

        if (!$user) {
            $user = $this->users->createGoogleUser(
                googleSub: $identity->subject(),
                email: $identity->email(),
                displayName: $identity->displayName(),
                avatarUrl: $identity->avatarUrl(),
                emailVerified: $identity->emailVerified(),
            );
        } else {
            $user = $this->users->updateGoogleProfile(
                user: $user,
                email: $identity->email(),
                displayName: $identity->displayName(),
                avatarUrl: $identity->avatarUrl(),
                emailVerified: $identity->emailVerified(),
            );
        }

        return $this->auth->login(
            user: $user,
            authenticationMethod: 'google',
        );
    }
}