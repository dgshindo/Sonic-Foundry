<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use SonicFoundry\Database\Connection;

try {
    $pdo = Connection::get();

    $result = $pdo->query(
        'SELECT VERSION() AS mysql_version'
    )->fetch();

    $version = $result['mysql_version'] ?? 'Unknown';
} catch (Throwable $exception) {
    http_response_code(500);
    $error = $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Test | Sonic Foundry</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <main class="app-shell">
        <section class="welcome-panel">
            <p class="eyebrow">System Check</p>

            <h1>Database Connection</h1>

            <?php if (isset($error)): ?>
                <p class="status" style="color:#ffb4b4;">
                    Connection failed:
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php else: ?>
                <p class="status">
                    MySQL connection successful.
                </p>

                <p>
                    Server version:
                    <strong><?= htmlspecialchars($version) ?></strong>
                </p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>