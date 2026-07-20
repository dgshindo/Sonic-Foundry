<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;
use SonicFoundry\Memory\MemoryExtraction;

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

$authenticatedUser =
    $auth->requireAuthentication();

$works = $container
    ->workService()
    ->listForUser(
        $authenticatedUser
    );

$selectedWorkId = filter_input(
    INPUT_GET,
    'work',
    FILTER_VALIDATE_INT
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedWorkId = filter_input(
        INPUT_POST,
        'work_id',
        FILTER_VALIDATE_INT
    );

    if (
        is_int($submittedWorkId)
        && $submittedWorkId > 0
    ) {
        $selectedWorkId =
            $submittedWorkId;
    }
}

$selectedWork = null;

foreach ($works as $work) {
    if ($work->id() === $selectedWorkId) {
        $selectedWork = $work;
        break;
    }
}

$extraction = null;
$savedMemory = null;
$existingMemory = null;
$error = null;
$elapsedMilliseconds = null;

$action = trim(
    (string) ($_POST['action'] ?? '')
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    if (!Session::verifyCsrfToken($csrfToken)) {
        $error = (
            'Your form session expired. '
            . 'Refresh and try again.'
        );
    } elseif ($selectedWork === null) {
        $error = 'Select a valid Work.';
    } elseif (
        !in_array(
            $action,
            [
                'preview',
                'save',
            ],
            true
        )
    ) {
        $error =
            'Select a valid extraction action.';
    } else {
        try {
            $startedAt = hrtime(true);

            $extraction = $container
                ->emotionMemoryExtractor()
                ->extract(
                    user: $authenticatedUser,
                    workId: $selectedWork->id(),
                );

            $elapsedMilliseconds = round(
                (
                    hrtime(true)
                    - $startedAt
                ) / 1_000_000,
                2
            );

            if ($action === 'save') {
                $savedMemory = $container
                    ->memoryService()
                    ->propose(
                        user: $authenticatedUser,
                        workId: $selectedWork->id(),
                        pillarValue: 'emotion',
                        extraction: $extraction,
                    );
            }
        } catch (\Throwable $exception) {
            $error = sprintf(
                '%s: %s in %s:%d',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            );
        }
    }
}

if ($selectedWork !== null) {
    try {
        $existingMemory = $container
            ->memoryService()
            ->memoryForWork(
                user: $authenticatedUser,
                workId: $selectedWork->id(),
                pillarValue: 'emotion',
            );
    } catch (\Throwable $exception) {
        if ($error === null) {
            $error =
                $exception->getMessage();
        }
    }
}

/**
 * @param array<string, mixed> $data
 */
function displayEmotionDocument(
    array $data,
): void {
    $textFields = [
        'emotional_core' =>
            'Emotional Core',

        'starting_emotion' =>
            'Starting Emotion',

        'ending_emotion' =>
            'Ending Emotion',

        'emotional_arc' =>
            'Emotional Arc',

        'emotional_stakes' =>
            'Emotional Stakes',

        'desired_listener_feeling' =>
            'Desired Listener Feeling',
    ];

    $listFields = [
        'emotional_contrasts' =>
            'Emotional Contrasts',

        'emotional_touchstones' =>
            'Emotional Touchstones',
    ];
    ?>
    <dl class="emotion-memory-fields">
        <?php foreach (
            $textFields
            as $key => $label
        ): ?>
            <div>
                <dt>
                    <?= htmlspecialchars(
                        $label,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </dt>

                <dd>
                    <?= nl2br(
                        htmlspecialchars(
                            is_string(
                                $data[$key] ?? null
                            )
                                && trim(
                                    $data[$key]
                                ) !== ''
                                    ? $data[$key]
                                    : 'Not established',
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ) ?>
                </dd>
            </div>
        <?php endforeach; ?>

        <?php foreach (
            $listFields
            as $key => $label
        ): ?>
            <?php
            $values = is_array(
                $data[$key] ?? null
            )
                ? $data[$key]
                : [];
            ?>

            <div>
                <dt>
                    <?= htmlspecialchars(
                        $label,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </dt>

                <dd>
                    <?= htmlspecialchars(
                        $values !== []
                            ? implode(', ', $values)
                            : 'None established',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </dd>
            </div>
        <?php endforeach; ?>
    </dl>
    <?php
}

function renderExtraction(
    MemoryExtraction $extraction,
): void {
    displayEmotionDocument(
        $extraction->data()
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

    <title>
        Emotion Memory Test | Sonic Foundry
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >

    <style>
        .emotion-test-page {
            min-height: 100vh;
            padding:
                var(--space-10)
                var(--space-6);

            background:
                radial-gradient(
                    circle at 50% 0%,
                    rgba(255, 106, 0, 0.12),
                    transparent 34rem
                ),
                var(--color-background);
        }

        .emotion-test-container {
            width: min(100%, 1100px);
            margin-inline: auto;
        }

        .emotion-test-card {
            margin-bottom: var(--space-6);
            padding: var(--space-8);

            background: var(--color-panel);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
        }

        .emotion-test-form {
            display: grid;
            gap: var(--space-5);
        }

        .emotion-test-form select {
            width: 100%;
            padding: var(--space-3);

            color: var(--color-text);
            background: var(--color-panel-raised);

            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
        }

        .emotion-test-actions {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-4);
        }

        .emotion-memory-fields {
            display: grid;
            gap: 0;
            margin: 0;
        }

        .emotion-memory-fields > div {
            display: grid;
            grid-template-columns:
                minmax(11rem, 0.32fr)
                minmax(0, 1fr);
            gap: var(--space-6);

            padding:
                var(--space-5)
                0;

            border-bottom:
                1px solid
                var(--color-border-muted);
        }

        .emotion-memory-fields dt {
            color: var(--color-text-muted);

            font-size: var(--font-size-xs);
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .emotion-memory-fields dd {
            margin: 0;

            color: var(--color-text-soft);
            line-height: 1.6;
        }

        .emotion-test-error {
            border-left:
                3px solid
                var(--color-error);
        }

        .emotion-test-success {
            border-left:
                3px solid
                var(--color-success, #9ed59e);
        }

        @media (max-width: 700px) {
            .emotion-memory-fields > div {
                grid-template-columns: 1fr;
                gap: var(--space-2);
            }
        }
    </style>
</head>

<body>
    <main class="emotion-test-page">
        <div class="emotion-test-container">
            <section class="emotion-test-card">
                <p class="eyebrow">
                    Memory Engine Diagnostic
                </p>

                <h1>
                    Emotion Memory Extraction
                </h1>

                <p>
                    This analyzes the Emotion conversation and
                    uses confirmed Story Memory as contextual
                    grounding.
                </p>
            </section>

            <section class="emotion-test-card">
                <form
                    class="emotion-test-form"
                    method="post"
                    action="/dev/emotion-memory-test.php"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            Session::csrfToken(),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <label for="work_id">
                        Work
                    </label>

                    <select
                        id="work_id"
                        name="work_id"
                        required
                    >
                        <option value="">
                            Select a Work
                        </option>

                        <?php foreach ($works as $work): ?>
                            <option
                                value="<?= $work->id() ?>"
                                <?= $selectedWorkId === $work->id()
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $work->title()
                                    . ' — '
                                    . $work->typeLabel(),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="emotion-test-actions">
                        <button
                            class="button button--primary"
                            type="submit"
                            name="action"
                            value="preview"
                        >
                            Extract Preview
                        </button>

                        <button
                            class="button button--secondary"
                            type="submit"
                            name="action"
                            value="save"
                        >
                            Extract and Save as Proposed
                        </button>

                        <a
                            class="button button--ghost"
                            href="/workspace.php"
                        >
                            Return to Workspace
                        </a>
                    </div>
                </form>
            </section>

            <?php if ($error !== null): ?>
                <section
                    class="
                        emotion-test-card
                        emotion-test-error
                    "
                    role="alert"
                >
                    <h2>
                        Extraction Failed
                    </h2>

                    <p>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                </section>
            <?php endif; ?>

            <?php if ($extraction !== null): ?>
                <section
                    class="
                        emotion-test-card
                        emotion-test-success
                    "
                >
                    <p class="eyebrow">
                        Proposed Understanding
                    </p>

                    <h2>
                        Extracted Emotion Memory
                    </h2>

                    <?php renderExtraction($extraction); ?>

                    <?php if (
                        $elapsedMilliseconds !== null
                    ): ?>
                        <p>
                            Extraction time:
                            <?= htmlspecialchars(
                                (string) $elapsedMilliseconds,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                            ms
                        </p>
                    <?php endif; ?>

                    <?php if ($savedMemory !== null): ?>
                        <p>
                            Saved as proposed revision
                            <?= $savedMemory->revision() ?>.
                        </p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($existingMemory !== null): ?>
                <section class="emotion-test-card">
                    <p class="eyebrow">
                        Persisted Memory
                    </p>

                    <h2>
                        Current Stored Emotion State
                    </h2>

                    <p>
                        Status:
                        <strong>
                            <?= htmlspecialchars(
                                $existingMemory->statusLabel(),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>
                    </p>

                    <p>
                        Revision:
                        <strong>
                            <?= $existingMemory->revision() ?>
                        </strong>
                    </p>

                    <?php displayEmotionDocument(
                        $existingMemory->data()
                    ); ?>
                </section>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>