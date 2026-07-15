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
            <div class="workspace-header">
                <a href="/" class="workspace-logo-link" aria-label="Sonic Foundry Home">
                    <img
                        class="workspace-logo"
                        src="/assets/images/sonic-foundry-logo.png"
                        alt="Sonic Foundry anvil and soundwave emblem"
                    >
                </a>
                <?php if ($authenticatedUser->avatarUrl()): ?>
                                <img
                                    class="workspace-avatar"
                                    src="<?= htmlspecialchars($authenticatedUser->avatarUrl()) ?>"
                                    alt="<?= htmlspecialchars($authenticatedUser->displayName()) ?> profile picture"
                                    referrerpolicy="no-referrer"
                                >
                <?php else: ?>
                    <div class="user-avatar user-avatar--fallback" aria-hidden="true" >
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($authenticatedUser->displayName(0,1)))) ?>
                    </div>
                <?php endif; ?>
                <div class="workspace-title-group">
                        <div class="eyebrow">
                            Workspace
                        </div>

                        <div class="display-title display-title--small">
                            
                            Welcome, <?= htmlspecialchars(explode(' ', trim($authenticatedUser->displayName()))[0]) ?>
                        </div>

                        <div class="tagline">
                            Continue forging your next legacy.
                        </p>
                    </div>
                    </div>
            <p>
                Signed in with
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