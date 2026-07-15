<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use SonicFoundry\Database\Connection;
use SonicFoundry\User\UserRepository;

$repository = new UserRepository(
    Connection::get()
);

$email = 'development-user@example.com';

$user = $repository->findByEmail($email);

if (!$user) {
    $user = $repository->createGoogleUser(
        googleSub: 'development-google-sub-001',
        email: $email,
        displayName: 'Development User',
        avatarUrl: null,
        emailVerified: true,
    );
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>User Repository Test | Sonic Foundry</title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>
<body>
    <main class="app-shell">
        <section class="welcome-panel">
            <p class="eyebrow">Repository Check</p>

            <h1>User Repository</h1>

            <p class="status">
                User loaded successfully.
            </p>

            <p>
                ID:
                <strong>
                    <?= htmlspecialchars(
                        (string) $user->id(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </p>

            <p>
                Name:
                <strong>
                    <?= htmlspecialchars(
                        $user->displayName(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </p>

            <p>
                Email:
                <strong>
                    <?= htmlspecialchars(
                        $user->email(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </p>

            <p>
                Google identity:
                <strong>
                    <?= $user->hasGoogleIdentity()
                        ? 'Yes'
                        : 'No' ?>
                </strong>
            </p>
        </section>
    </main>
</body>
</html>