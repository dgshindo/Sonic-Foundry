<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

$action = $_GET['action'] ?? null;

if ($action === 'set') {
    Session::put('test_name', 'Michael Bell');
    Session::flash(
        'status',
        'Session value was saved successfully.'
    );

    header('Location: /session-test.php');
    exit;
}

if ($action === 'clear') {
    Session::forget('test_name');
    Session::flash(
        'status',
        'Session value was removed.'
    );

    header('Location: /session-test.php');
    exit;
}

$name = Session::get('test_name');
$status = Session::getFlash('status');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Session Test | Sonic Foundry</title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>
<body>
    <main class="app-shell">
        <section class="welcome-panel">
            <p class="eyebrow">System Check</p>

            <h1>Session Test</h1>

            <?php if ($status): ?>
                <p class="status">
                    <?= htmlspecialchars($status) ?>
                </p>
            <?php endif; ?>

            <p>
                Stored name:
                <strong>
                    <?= htmlspecialchars(
                        $name ?? 'No value stored'
                    ) ?>
                </strong>
            </p>

            <p>
                Session ID:
                <code>
                    <?= htmlspecialchars(session_id()) ?>
                </code>
            </p>

            <div class="hero__actions">
                <a
                    class="button button--primary"
                    href="/session-test.php?action=set"
                >
                    Set Session Value
                </a>

                <a
                    class="button button--secondary"
                    href="/session-test.php?action=clear"
                >
                    Clear Session Value
                </a>
            </div>
        </section>
    </main>
</body>
</html>