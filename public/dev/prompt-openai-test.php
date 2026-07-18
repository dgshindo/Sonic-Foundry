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
$responseText = '';
$assembledPrompt = '';
$elapsedMilliseconds = null;

try {
    $assembledPrompt = $container
        ->prompts()
        ->assemble(
            promptPaths: [
                'core/creative-partner.md',
                'pillars/story.md',
            ],

            variables: [
                'creator_first_name' => 'Michael',
                'work_title' => 'Prompt Pipeline Test',
                'work_type' => 'Single',
                'pillar_name' => 'Story',
            ],
        );

    $startedAt = hrtime(true);

    $responseText = $container
        ->openAI()
        ->streamResponse(
            instructions: $assembledPrompt,

            input: [
                [
                    'role' => 'user',
                    'content' => (
                        'This Work is about someone rebuilding '
                        . 'their life after losing everything. '
                        . 'Respond as the Story Guide.'
                    ),
                ],
            ],

            onTextDelta: static function (
                string $delta
            ): void {
                /*
                 * The diagnostic collects the completed response.
                 * Browser streaming is tested separately in the Forge.
                 */
            },
        );

    $elapsedMilliseconds = round(
        (
            hrtime(true)
            - $startedAt
        ) / 1_000_000,
        2
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
        Prompt + OpenAI Test | Sonic Foundry
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >

    <style>
        .pipeline-test-page {
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

        .pipeline-test-container {
            width: min(100%, 1100px);
            margin-inline: auto;
        }

        .pipeline-test-card {
            margin-bottom: var(--space-6);
            padding: var(--space-8);

            background: var(--color-panel);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-medium);
        }

        .pipeline-test-card h1,
        .pipeline-test-card h2 {
            margin-bottom: var(--space-4);
        }

        .pipeline-test-success {
            border-left: 3px solid var(--color-ember);
        }

        .pipeline-test-error {
            border-left: 3px solid var(--color-error);
        }

        .pipeline-test-output {
            max-height: 600px;
            padding: var(--space-5);
            overflow: auto;

            color: var(--color-text-soft);
            white-space: pre-wrap;
            overflow-wrap: anywhere;

            background: var(--color-background-soft);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);

            font-family: var(--font-monospace);
            font-size: var(--font-size-sm);
            line-height: 1.6;
        }

        .pipeline-test-metadata {
            display: grid;
            gap: var(--space-3);
            margin-top: var(--space-6);
        }

        .pipeline-test-metadata div {
            display: grid;
            grid-template-columns: 10rem minmax(0, 1fr);
            gap: var(--space-4);

            padding-bottom: var(--space-3);

            border-bottom: 1px solid var(--color-border-muted);
        }

        .pipeline-test-metadata dt {
            color: var(--color-text-muted);
            font-size: var(--font-size-xs);
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .pipeline-test-metadata dd {
            margin: 0;
            color: var(--color-text-soft);
        }

        .pipeline-test-actions {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-4);

            margin-top: var(--space-8);
        }
    </style>
</head>

<body>
    <main class="pipeline-test-page">
        <div class="pipeline-test-container">
            <section class="pipeline-test-card">
                <p class="eyebrow">
                    Development Diagnostic
                </p>

                <h1>
                    Prompt + OpenAI Pipeline Test
                </h1>

                <p>
                    This tests the Markdown prompt system and the
                    application OpenAI client together, while bypassing
                    conversation persistence and the Creative Partner
                    service.
                </p>
            </section>

            <?php if ($error !== null): ?>
                <section
                    class="
                        pipeline-test-card
                        pipeline-test-error
                    "
                >
                    <h2>
                        Pipeline Failed
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

                    <pre class="pipeline-test-output"><?= htmlspecialchars(
                        $error['trace'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></pre>
                </section>
            <?php else: ?>
                <section
                    class="
                        pipeline-test-card
                        pipeline-test-success
                    "
                >
                    <h2>
                        Pipeline Passed
                    </h2>

                    <p>
                        The assembled Story persona successfully produced
                        a response through the application OpenAI client.
                    </p>

                    <pre class="pipeline-test-output"><?= htmlspecialchars(
                        $responseText,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></pre>

                    <dl class="pipeline-test-metadata">
                        <div>
                            <dt>
                                Model
                            </dt>

                            <dd>
                                <?= htmlspecialchars(
                                    (string) env(
                                        'OPENAI_MODEL',
                                        'Not configured'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Request time
                            </dt>

                            <dd>
                                <?= htmlspecialchars(
                                    $elapsedMilliseconds !== null
                                        ? $elapsedMilliseconds . ' ms'
                                        : 'Not completed',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                Prompt length
                            </dt>

                            <dd>
                                <?= mb_strlen($assembledPrompt) ?>
                                characters
                            </dd>
                        </div>
                    </dl>
                </section>
            <?php endif; ?>

            <section class="pipeline-test-card">
                <h2>
                    Assembled Instructions
                </h2>

                <pre class="pipeline-test-output"><?= htmlspecialchars(
                    $assembledPrompt,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></pre>

                <div class="pipeline-test-actions">
                    <a
                        class="button button--primary"
                        href="/dev/prompt-openai-test.php"
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