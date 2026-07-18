<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/config/bootstrap.php';

$appEnvironment = strtolower(
    (string) env(
        'APP_ENV',
        'production'
    )
);

if ($appEnvironment !== 'local') {
    http_response_code(404);
    exit('Not Found');
}

$error = null;
$assembledPrompt = null;
$promptRoot = dirname(__DIR__, 2)
    . '/prompts';

$files = [
    'core/creative-partner.md',
    'pillars/story.md',
];

$fileStatus = [];

foreach ($files as $relativePath) {
    $fullPath = $promptRoot
        . DIRECTORY_SEPARATOR
        . str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $relativePath
        );

    $fileStatus[$relativePath] = [
        'path' => $fullPath,
        'exists' => is_file($fullPath),
        'readable' => is_readable($fullPath),
    ];
}

try {
    $assembledPrompt = $container
        ->prompts()
        ->assemble(
            promptPaths: $files,

            variables: [
                'creator_first_name' => 'Michael',
                'work_title' => 'Prompt Diagnostic',
                'work_type' => 'Single',
                'pillar_name' => 'Story',
            ],
        );
} catch (\Throwable $exception) {
    $error = [
        'class' => $exception::class,
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ];
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

    <title>
        Prompt System Test | Sonic Foundry
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >

    <style>
        .prompt-test-page {
            min-height: 100vh;
            padding: var(--space-10);

            background:
                radial-gradient(
                    circle at 50% 0%,
                    rgba(255, 106, 0, 0.12),
                    transparent 30rem
                ),
                var(--color-background);
        }

        .prompt-test-container {
            width: min(100%, 1100px);
            margin-inline: auto;
        }

        .prompt-test-card {
            margin-bottom: var(--space-6);
            padding: var(--space-8);

            background: var(--color-panel);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-medium);
        }

        .prompt-test-card h1,
        .prompt-test-card h2 {
            margin-bottom: var(--space-4);
        }

        .prompt-test-status {
            display: grid;
            gap: var(--space-4);
        }

        .prompt-test-status__item {
            padding: var(--space-4);

            background: rgba(255, 255, 255, 0.018);
            border: 1px solid var(--color-border-muted);
            border-radius: var(--radius-md);
        }

        .prompt-test-status__item strong {
            display: block;
            margin-bottom: var(--space-2);
        }

        .prompt-test-status__item code {
            overflow-wrap: anywhere;
        }

        .prompt-test-success {
            border-left: 3px solid var(--color-ember);
        }

        .prompt-test-error {
            border-left: 3px solid var(--color-error);
        }

        .prompt-test-output {
            max-height: 600px;
            padding: var(--space-5);
            overflow: auto;

            color: var(--color-text-soft);
            white-space: pre-wrap;

            background: var(--color-background-soft);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);

            font-family: var(--font-monospace);
            font-size: var(--font-size-sm);
            line-height: 1.6;
        }

        .prompt-test-actions {
            display: flex;
            gap: var(--space-4);
            margin-top: var(--space-6);
        }
    </style>
</head>

<body>
    <main class="prompt-test-page">
        <div class="prompt-test-container">
            <section class="prompt-test-card">
                <p class="eyebrow">
                    Development Diagnostic
                </p>

                <h1>
                    Prompt System Test
                </h1>

                <p>
                    This page tests Markdown loading, front matter parsing,
                    variable replacement, and prompt assembly without
                    contacting OpenAI.
                </p>
            </section>

            <section class="prompt-test-card">
                <h2>
                    Prompt Files
                </h2>

                <div class="prompt-test-status">
                    <?php foreach (
                        $fileStatus
                        as $relativePath => $status
                    ): ?>
                        <div class="prompt-test-status__item">
                            <strong>
                                <?= htmlspecialchars(
                                    $relativePath,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                            <p>
                                Exists:
                                <?= $status['exists']
                                    ? 'Yes'
                                    : 'No' ?>
                            </p>

                            <p>
                                Readable:
                                <?= $status['readable']
                                    ? 'Yes'
                                    : 'No' ?>
                            </p>

                            <code>
                                <?= htmlspecialchars(
                                    $status['path'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </code>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($error !== null): ?>
                <section
                    class="
                        prompt-test-card
                        prompt-test-error
                    "
                >
                    <h2>
                        Prompt Assembly Failed
                    </h2>

                    <p>
                        <strong>Exception:</strong>
                        <?= htmlspecialchars(
                            $error['class'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p>
                        <strong>Message:</strong>
                        <?= htmlspecialchars(
                            $error['message'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p>
                        <strong>Source:</strong>
                        <?= htmlspecialchars(
                            $error['file']
                            . ':'
                            . $error['line'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <pre class="prompt-test-output"><?= htmlspecialchars(
                        $error['trace'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></pre>
                </section>
            <?php else: ?>
                <section
                    class="
                        prompt-test-card
                        prompt-test-success
                    "
                >
                    <h2>
                        Prompt Assembly Passed
                    </h2>

                    <p>
                        The following text is exactly what would be sent
                        to the OpenAI instructions field.
                    </p>

                    <pre class="prompt-test-output"><?= htmlspecialchars(
                        (string) $assembledPrompt,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></pre>
                </section>
            <?php endif; ?>

            <section class="prompt-test-card">
                <h2>
                    Prompt Root
                </h2>

                <code>
                    <?= htmlspecialchars(
                        $promptRoot,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </code>

                <div class="prompt-test-actions">
                    <a
                        class="button button--primary"
                        href="/dev/prompt-test.php"
                    >
                        Run Again
                    </a>

                    <a
                        class="button button--secondary"
                        href="/workspace.php"
                    >
                        Return to Workspace
                    </a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>