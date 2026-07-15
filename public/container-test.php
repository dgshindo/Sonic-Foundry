<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

$firstAuth = $container->auth();
$secondAuth = $container->auth();

$firstUsers = $container->users();
$secondUsers = $container->users();

$authShared = $firstAuth === $secondAuth;
$usersShared = $firstUsers === $secondUsers;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Container Test | Sonic Foundry</title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>
<body>
    <main class="app-shell">
        <section class="welcome-panel">
            <p class="eyebrow">
                Application Check
            </p>

            <h1>Service Container</h1>

            <p class="status">
                Container initialized successfully.
            </p>

            <p>
                Shared Auth instance:
                <strong>
                    <?= $authShared ? 'Yes' : 'No' ?>
                </strong>
            </p>

            <p>
                Shared UserRepository instance:
                <strong>
                    <?= $usersShared ? 'Yes' : 'No' ?>
                </strong>
            </p>
        </section>
    </main>
</body>
</html>