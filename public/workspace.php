<?php
declare(strict_types=1);

$services = require dirname(__DIR__) . '/config/bootstrap.php';

$auth = $services['auth'];

$authenticatedUser = $auth->requireAuthentication();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Workspace | Sonic Foundry</title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>
<body>
    <main class="app-shell">
        <section class="welcome-panel">
            <?php if ($authenticatedUser->avatarUrl()): ?>
                <img
                    src="<?= htmlspecialchars(
                        $authenticatedUser->avatarUrl(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    alt=""
                    width="96"
                    height="96"
                    style="
                        margin: 0 auto 1.5rem;
                        border-radius: 50%;
                    "
                >
            <?php endif; ?>

            <p class="eyebrow">
                Workspace
            </p>

            <h1>
                Welcome,
                <?= htmlspecialchars(
                    $authenticatedUser->displayName(),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h1>

            <p class="tagline">
                Continue forging your next legacy.
            </p>

            <p>
                Signed in through
                <strong>
                    <?= htmlspecialchars(
                        $authenticatedUser->authenticationMethod(),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </p>

            <div class="hero__actions">
                <a
                    class="button button--primary"
                    href="#"
                >
                    Begin New Project
                </a>

                <a
                    class="button button--secondary"
                    href="/logout.php"
                >
                    Sign Out
                </a>
            </div>
        </section>
    </main>
</body>
</html>