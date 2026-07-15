<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

if ($auth->check()) {
    header('Location: /workspace.php');
    exit;
}

$error = Session::getFlash('auth_error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Enter the Forge | Sonic Foundry</title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>
<body>
    <main class="app-shell">
        <section class="welcome-panel">
            <p class="eyebrow">
                Authentication
            </p>

            <h1>Enter the Forge</h1>

            <p class="tagline">
                Continue shaping your sound and legacy.
            </p>

            <?php if ($error): ?>
                <p class="status">
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            <?php endif; ?>

            <div class="hero__actions">
                <a
                    class="button button--primary"
                    href="/development-login.php"
                >
                    Development Login
                </a>

                <a
                    class="button button--secondary"
                    href="/"
                >
                    Return Home
                </a>
            </div>

            <p
                style="
                    margin-top: 2rem;
                    color: var(--text-muted);
                "
            >
                Google authentication will replace the temporary
                development login in the next authentication phase.
            </p>
        </section>
    </main>
</body>
</html>