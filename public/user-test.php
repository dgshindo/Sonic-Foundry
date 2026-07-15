<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

//use DateTimeImmutable;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\User\User;

$user = new User(
    id: 1,
    googleSub: null,
    email: 'michael@example.com',
    displayName: 'Michael Bell',
    avatarUrl: null,
    passwordHash: null,
    emailVerifiedAt: new DateTimeImmutable(),
    accountStatus: 'active',
    createdAt: new DateTimeImmutable(),
    updatedAt: new DateTimeImmutable(),
);

$authenticatedUser = new AuthenticatedUser(
    user: $user,
    authenticatedAt: new DateTimeImmutable(),
    authenticationMethod: 'development',
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>User Domain Test | Sonic Foundry</title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>
<body>
    <main class="app-shell">
        <section class="welcome-panel">
            <p class="eyebrow">Domain Check</p>

            <h1>User Test</h1>

            <p>
                Authenticated as
                <strong>
                    <?= htmlspecialchars(
                        $authenticatedUser->displayName(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </p>

            <p>
                <?= htmlspecialchars(
                    $authenticatedUser->email(),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <p class="status">
                Authentication method:
                <?= htmlspecialchars(
                    $authenticatedUser->authenticationMethod(),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        </section>
    </main>
</body>
</html>