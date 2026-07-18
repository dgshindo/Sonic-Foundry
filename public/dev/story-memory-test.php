<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;
use SonicFoundry\Memory\MemoryExtraction;

/*
|--------------------------------------------------------------------------
| Local-development protection
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$authenticatedUser = $auth->requireAuthentication();

/*
|--------------------------------------------------------------------------
| Available Works
|--------------------------------------------------------------------------
*/

$works = $container
    ->workService()
    ->listForUser(
        $authenticatedUser
    );

/*
|--------------------------------------------------------------------------
| Request state
|--------------------------------------------------------------------------
*/

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
        $selectedWorkId = $submittedWorkId;
    }
}

$selectedWork = null;

foreach ($works as $work) {
    if ($work->id() === $selectedWorkId) {
        $selectedWork = $work;
        break;
    }
}

/*
|--------------------------------------------------------------------------
| Extraction state
|--------------------------------------------------------------------------
*/

$extraction = null;
$savedMemory = null;
$error = null;
$elapsedMilliseconds = null;

$requestedAction = trim(
    (string) ($_POST['action'] ?? '')
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    if (!Session::verifyCsrfToken($csrfToken)) {
        $error = (
            'Your form session expired. '
            . 'Refresh the page and try again.'
        );
    } elseif ($selectedWork === null) {
        $error = 'Select a valid Work.';
    } elseif (
        !in_array(
            $requestedAction,
            [
                'preview',
                'save',
            ],
            true
        )
    ) {
        $error = 'Select a valid extraction action.';
    } else {
        try {
            $startedAt = hrtime(true);

            $extraction = $container
                ->storyMemoryExtractor()
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

            if ($requestedAction === 'save') {
                $savedMemory = $container
                    ->memoryService()
                    ->propose(
                        user: $authenticatedUser,
                        workId: $selectedWork->id(),
                        pillarValue: 'story',
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

/*
|--------------------------------------------------------------------------
| Existing persisted memory
|--------------------------------------------------------------------------
*/

$existingMemory = null;

if ($selectedWork !== null) {
    try {
        $existingMemory = $container
            ->memoryService()
            ->memoryForWork(
                user: $authenticatedUser,
                workId: $selectedWork->id(),
                pillarValue: 'story',
            );
    } catch (\Throwable $exception) {
        if ($error === null) {
            $error = sprintf(
                '%s: %s',
                $exception::class,
                $exception->getMessage(),
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| Presentation helpers
|--------------------------------------------------------------------------
*/

function displayNullableText(
    ?string $value
): string {
    return $value !== null
        ? $value
        : 'Not established';
}

/**
 * @param list<string> $values
 */
function displayList(
    array $values
): string {
    return $values !== []
        ? implode(', ', $values)
        : 'None extracted';
}

function confidenceLabel(
    ?float $confidence
): string {
    if ($confidence === null) {
        return 'Not returned';
    }

    return number_format(
        $confidence * 100,
        1
    ) . '%';
}

function renderExtraction(
    MemoryExtraction $extraction
): void {
    ?>
    <dl class="memory-test-fields">
        <div>
            <dt>Summary</dt>

            <dd>
                <?= nl2br(
                    htmlspecialchars(
                        displayNullableText(
                            $extraction->summary()
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ) ?>
            </dd>
        </div>

        <div>
            <dt>Perspective</dt>

            <dd>
                <?= htmlspecialchars(
                    displayNullableText(
                        $extraction->perspective()
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </dd>
        </div>

        <div>
            <dt>Core Tension</dt>

            <dd>
                <?= nl2br(
                    htmlspecialchars(
                        displayNullableText(
                            $extraction->coreTension()
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ) ?>
            </dd>
        </div>

        <div>
            <dt>Listener Takeaway</dt>

            <dd>
                <?= nl2br(
                    htmlspecialchars(
                        displayNullableText(
                            $extraction->listenerTakeaway()
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ) ?>
            </dd>
        </div>

        <div>
            <dt>Themes</dt>

            <dd>
                <?= htmlspecialchars(
                    displayList(
                        $extraction->themes()
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </dd>
        </div>

        <div>
            <dt>Key Subjects</dt>

            <dd>
                <?= htmlspecialchars(
                    displayList(
                        $extraction->keySubjects()
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </dd>
        </div>

        <div>
            <dt>Extraction Confidence</dt>

            <dd>
                <?= htmlspecialchars(
                    confidenceLabel(
                        $extraction->confidence()
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </dd>
        </div>
    </dl>
    <?php
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
        Story Memory Test | Sonic Foundry
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >

    <style>
        .memory-test-page {
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

        .memory-test-container {
            width: min(100%, 1100px);
            margin-inline: auto;
        }

        .memory-test-card {
            margin-bottom: var(--space-6);
            padding:
                clamp(
                    var(--space-6),
                    4vw,
                    var(--space-10)
                );

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.035),
                    rgba(255, 255, 255, 0.008)
                ),
                var(--color-panel);

            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);

            box-shadow: var(--shadow-medium);
        }

        .memory-test-card--success {
            border-left:
                3px solid
                var(--color-ember);
        }

        .memory-test-card--error {
            border-left:
                3px solid
                var(--color-error);
        }

        .memory-test-card h1,
        .memory-test-card h2 {
            margin-bottom: var(--space-4);
        }

        .memory-test-introduction {
            max-width: 48rem;
            margin-bottom: 0;

            color: var(--color-text-muted);
            line-height: 1.7;
        }

        .memory-test-form {
            display: grid;
            gap: var(--space-5);
        }

        .memory-test-form__field {
            display: grid;
            gap: var(--space-2);
        }

        .memory-test-form label {
            color: var(--color-silver);
            font-weight: 700;
        }

        .memory-test-form select {
            width: 100%;
            padding: var(--space-3);

            color: var(--color-text);
            background: var(--color-panel-raised);

            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
        }

        .memory-test-actions {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-4);
        }

        .memory-test-fields {
            display: grid;
            gap: 0;
            margin: 0;
        }

        .memory-test-fields > div {
            display: grid;
            grid-template-columns:
                minmax(10rem, 0.32fr)
                minmax(0, 1fr);
            gap: var(--space-6);

            padding:
                var(--space-5)
                0;

            border-bottom:
                1px solid
                var(--color-border-muted);
        }

        .memory-test-fields > div:last-child {
            border-bottom: 0;
        }

        .memory-test-fields dt {
            color: var(--color-text-muted);

            font-size: var(--font-size-xs);
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .memory-test-fields dd {
            margin: 0;

            color: var(--color-text-soft);
            line-height: 1.65;
        }

        .memory-test-metadata {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-4);

            margin-bottom: var(--space-6);

            color: var(--color-text-muted);
            font-size: var(--font-size-sm);
        }

        .memory-test-status {
            display: inline-flex;
            align-items: center;

            padding:
                var(--space-1)
                var(--space-3);

            color: var(--color-gold);
            background: rgba(213, 170, 93, 0.08);

            border: 1px solid var(--color-border);
            border-radius: var(--radius-round);

            font-size: var(--font-size-xs);
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        @media (max-width: 700px) {
            .memory-test-fields > div {
                grid-template-columns: 1fr;
                gap: var(--space-2);
            }

            .memory-test-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .memory-test-actions .button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="memory-test-page">
        <div class="memory-test-container">
            <section class="memory-test-card">
                <p class="eyebrow">
                    Memory Engine Diagnostic
                </p>

                <h1>
                    Story Memory Extraction
                </h1>

                <p class="memory-test-introduction">
                    This page analyzes the persisted Story conversation
                    for an owned Work. Previewing does not alter stored
                    memory. Saving creates or replaces the current
                    proposed memory and records a revision.
                </p>
            </section>

            <section class="memory-test-card">
                <h2>
                    Select Conversation
                </h2>

                <?php if ($works === []): ?>
                    <p>
                        No Works are available for extraction.
                    </p>
                <?php else: ?>
                    <form
                        class="memory-test-form"
                        method="post"
                        action="/dev/story-memory-test.php"
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

                        <div class="memory-test-form__field">
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
                        </div>

                        <div class="memory-test-actions">
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
                <?php endif; ?>
            </section>

            <?php if ($error !== null): ?>
                <section
                    class="
                        memory-test-card
                        memory-test-card--error
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
                        memory-test-card
                        memory-test-card--success
                    "
                >
                    <p class="eyebrow">
                        Proposed Understanding
                    </p>

                    <h2>
                        Extracted Story Memory
                    </h2>

                    <div class="memory-test-metadata">
                        <?php if ($selectedWork !== null): ?>
                            <span>
                                Work:
                                <strong>
                                    <?= htmlspecialchars(
                                        $selectedWork->title(),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </span>
                        <?php endif; ?>

                        <?php if (
                            $elapsedMilliseconds !== null
                        ): ?>
                            <span>
                                Extraction time:
                                <strong>
                                    <?= htmlspecialchars(
                                        (string) $elapsedMilliseconds,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                    ms
                                </strong>
                            </span>
                        <?php endif; ?>

                        <?php if ($savedMemory !== null): ?>
                            <span class="memory-test-status">
                                Saved as Proposed
                            </span>

                            <span>
                                Revision:
                                <strong>
                                    <?= $savedMemory->revision() ?>
                                </strong>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php renderExtraction($extraction); ?>
                </section>
            <?php endif; ?>

            <?php if ($existingMemory !== null): ?>
                <section class="memory-test-card">
                    <p class="eyebrow">
                        Persisted Memory
                    </p>

                    <h2>
                        Current Stored State
                    </h2>

                    <div class="memory-test-metadata">
                        <span class="memory-test-status">
                            <?= htmlspecialchars(
                                $existingMemory->statusLabel(),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                        <span>
                            Revision:
                            <strong>
                                <?= $existingMemory->revision() ?>
                            </strong>
                        </span>

                        <span>
                            Confidence:
                            <strong>
                                <?= htmlspecialchars(
                                    confidenceLabel(
                                        $existingMemory->confidence()
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </span>
                    </div>

                    <dl class="memory-test-fields">
                        <div>
                            <dt>Summary</dt>

                            <dd>
                                <?= nl2br(
                                    htmlspecialchars(
                                        displayNullableText(
                                            $existingMemory->summary()
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Perspective</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    displayNullableText(
                                        $existingMemory->perspective()
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Core Tension</dt>

                            <dd>
                                <?= nl2br(
                                    htmlspecialchars(
                                        displayNullableText(
                                            $existingMemory->coreTension()
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Listener Takeaway</dt>

                            <dd>
                                <?= nl2br(
                                    htmlspecialchars(
                                        displayNullableText(
                                            $existingMemory
                                                ->listenerTakeaway()
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Themes</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    displayList(
                                        $existingMemory->themes()
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Key Subjects</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    displayList(
                                        $existingMemory->keySubjects()
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>
                    </dl>
                </section>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>