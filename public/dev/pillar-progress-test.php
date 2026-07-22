<?php
declare(strict_types=1);

use SonicFoundry\Auth\Session;
use SonicFoundry\Progress\ProgressEvaluation;
use SonicFoundry\Work\WorkPillar;

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

$authenticatedUser = $auth->requireAuthentication();

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

$requestedPillar = strtolower(
    trim(
        (string) ($_GET['pillar'] ?? 'story')
    )
);

$pillar = WorkPillar::tryFrom(
    $requestedPillar
);

if (
    $pillar === null
    || !$container
        ->pillarRegistry()
        ->has($pillar)
) {
    $pillar = WorkPillar::Story;
}

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

$evaluation = null;
$savedProgress = null;
$existingProgress = null;
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
        $error = 'Select a valid evaluation action.';
    } else {
        try {
            $startedAt = hrtime(true);

            $evaluation = $container
                ->pillarProgressEvaluator()
                ->evaluate(
                    user: $authenticatedUser,
                    workId: $selectedWork->id(),
                    pillarValue: $pillar->value,
                );

            $elapsedMilliseconds = round(
                (
                    hrtime(true)
                    - $startedAt
                ) / 1_000_000,
                2
            );

            if ($action === 'save') {
                $savedProgress = $container
                    ->progressService()
                    ->recordEvaluation(
                        user: $authenticatedUser,
                        workId: $selectedWork->id(),
                        pillarValue: $pillar->value,
                        evaluation: $evaluation,
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
        $existingProgress = $container
            ->progressService()
            ->progressForWork(
                user: $authenticatedUser,
                workId: $selectedWork->id(),
                pillarValue: 'story',
            );
    } catch (\Throwable $exception) {
        if ($error === null) {
            $error = $exception->getMessage();
        }
    }
}

function renderEvaluation(
    ProgressEvaluation $evaluation,
): void {
    ?>
    <div class="progress-score">
        <strong>
            <?= $evaluation->readinessScore() ?>%
        </strong>

        <span>
            <?= $evaluation->isReady()
                ? 'Ready for Review'
                : 'Still Developing' ?>
        </span>
    </div>

    <div class="progress-criteria">
        <?php foreach (
            $evaluation->criteria()
            as $criterion
        ): ?>
            <article
                class="
                    progress-criterion
                    progress-criterion--<?= htmlspecialchars(
                        $criterion->status()->value,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                "
            >
                <header>
                    <h3>
                        <?= htmlspecialchars(
                            $criterion->label(),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h3>

                    <span>
                        <?= htmlspecialchars(
                            $criterion->statusLabel(),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>
                </header>

                <?php if (
                    $criterion->evidence() !== null
                ): ?>
                    <p>
                        <strong>Evidence:</strong>
                        <?= htmlspecialchars(
                            $criterion->evidence(),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>

                <?php if (
                    $criterion->guidance() !== null
                ): ?>
                    <p>
                        <strong>Guidance:</strong>
                        <?= htmlspecialchars(
                            $criterion->guidance(),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (
        $evaluation->recommendation() !== null
    ): ?>
        <div class="progress-recommendation">
            <strong>Recommendation</strong>

            <p>
                <?= htmlspecialchars(
                    $evaluation->recommendation(),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        </div>
    <?php endif; ?>
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
        <?= htmlspecialchars(
    $pillar->label()
) ?> Progress Test | Sonic Foundry
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >

    <style>
        .progress-test-page {
            min-height: 100vh;
            padding: var(--space-10) var(--space-6);
            background:
                radial-gradient(
                    circle at 50% 0%,
                    rgba(255, 106, 0, 0.12),
                    transparent 34rem
                ),
                var(--color-background);
        }

        .progress-test-container {
            width: min(100%, 1100px);
            margin-inline: auto;
        }

        .progress-test-card {
            margin-bottom: var(--space-6);
            padding: var(--space-8);
            background: var(--color-panel);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
        }

        .progress-test-form {
            display: grid;
            gap: var(--space-5);
        }

        .progress-test-form select {
            width: 100%;
            padding: var(--space-3);
            color: var(--color-text);
            background: var(--color-panel-raised);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
        }

        .progress-test-actions {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-4);
        }

        .progress-score {
            display: flex;
            align-items: baseline;
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .progress-score strong {
            color: var(--color-ember-bright);
            font-size: 3rem;
        }

        .progress-score span {
            color: var(--color-text-muted);
            font-weight: 800;
            text-transform: uppercase;
        }

        .progress-criteria {
            display: grid;
            gap: var(--space-4);
        }

        .progress-criterion {
            padding: var(--space-5);
            background: rgba(255, 255, 255, 0.018);
            border: 1px solid var(--color-border-muted);
            border-left: 3px solid var(--color-steel);
            border-radius: var(--radius-md);
        }

        .progress-criterion--emerging {
            border-left-color: var(--color-warning);
        }

        .progress-criterion--established {
            border-left-color:
                var(--color-success, #9ed59e);
        }

        .progress-criterion header {
            display: flex;
            justify-content: space-between;
            gap: var(--space-4);
            margin-bottom: var(--space-3);
        }

        .progress-criterion h3,
        .progress-criterion p {
            margin-bottom: 0;
        }

        .progress-criterion header span {
            color: var(--color-text-muted);
            font-size: var(--font-size-xs);
            font-weight: 800;
            text-transform: uppercase;
        }

        .progress-recommendation {
            margin-top: var(--space-6);
            padding: var(--space-5);
            background: rgba(255, 121, 0, 0.06);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
        }

        .progress-recommendation p {
            margin: var(--space-2) 0 0;
        }

        .progress-error {
            border-left: 3px solid var(--color-error);
        }
    </style>
</head>

<body>
    <main class="progress-test-page">
        <div class="progress-test-container">
            <section class="progress-test-card">
                <p class="eyebrow">
                    Generic Progress Engine Diagnostic
                </p>

                <h1>
                    <?= htmlspecialchars(
                        $pillar->label()
                    ) ?> Progress Evaluation
                </h1>

                <p>
                    This diagnostic evaluates confirmed 
                    <?= htmlspecialchars(
                        $pillar->label()
                    ) ?>  Memory.
                    Previewing does not alter persistent progress.
                </p>
                <select id="pillar-select">
                    <?php foreach (
                        $container
                            ->pillarRegistry()
                            ->all()
                        as $definition
                    ): ?>

                    <option
                        value="<?= htmlspecialchars(
                            $definition
                                ->pillar()
                                ->value
                        ) ?>"
                        <?= $definition
                                ->pillar()
                            === $pillar
                                ? 'selected'
                                : '' ?>
                    >

                    <?= htmlspecialchars(
                        $definition
                            ->pillar()
                            ->label()
                    ) ?>

                    </option>

                    <?php endforeach; ?>
                </select>

                <script>
                    document
                        .getElementById(
                            'pillar-select'
                        )
                        .addEventListener(
                            'change',
                            function () {

                                window.location =
                                    '?pillar='
                                    + this.value;

                            }
                        );
                </script>
            </section>

            <section class="progress-test-card">
                <form
                    class="progress-test-form"
                    method="post"
                    action="/dev/pillar-progress-test.php?pillar=<?= htmlspecialchars(
                        $pillar->value,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
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

                    <div class="progress-test-actions">
                        <button
                            class="button button--primary"
                            type="submit"
                            name="action"
                            value="preview"
                        >
                            Evaluate Preview
                        </button>

                        <button
                            class="button button--secondary"
                            type="submit"
                            name="action"
                            value="save"
                        >
                            Evaluate and Save
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
                        progress-test-card
                        progress-error
                    "
                    role="alert"
                >
                    <h2>
                        Evaluation Failed
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

            <?php if ($evaluation !== null): ?>
                <section class="progress-test-card">
                    <p class="eyebrow">
                        Evaluation Result
                    </p>

                    <h2>
                        Story Readiness
                    </h2>

                    <?php renderEvaluation($evaluation); ?>

                    <?php if (
                        $elapsedMilliseconds !== null
                    ): ?>
                        <p>
                            Evaluation time:
                            <?= htmlspecialchars(
                                (string) $elapsedMilliseconds,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                            ms
                        </p>
                    <?php endif; ?>

                    <?php if ($savedProgress !== null): ?>
                        <p>
                            Saved as revision
                            <?= $savedProgress->revision() ?>.
                        </p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($existingProgress !== null): ?>
                <section class="progress-test-card">
                    <p class="eyebrow">
                        Persisted Progress
                    </p>

                    <h2>
                        Current Stored Evaluation
                    </h2>

                    <p>
                        Status:
                        <strong>
                            <?= htmlspecialchars(
                                $existingProgress->statusLabel(),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>
                    </p>

                    <p>
                        Score:
                        <strong>
                            <?= $existingProgress
                                ->readinessScore() ?>%
                        </strong>
                    </p>

                    <p>
                        Revision:
                        <strong>
                            <?= $existingProgress->revision() ?>
                        </strong>
                    </p>
                </section>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>