<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

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

$error = null;
$success = null;

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
    } else {
        try {
            $completed = $container
                ->workflowService()
                ->complete(
                    user: $authenticatedUser,
                    workId: $selectedWork->id(),
                    pillarValue: 'story',
                );

            $success = (
                $completed->pillar()->label()
                . ' was completed successfully.'
            );
        } catch (\Throwable $exception) {
            $error = sprintf(
                '%s: %s',
                $exception::class,
                $exception->getMessage(),
            );
        }
    }
}

$workflow = [];

if ($selectedWork !== null) {
    try {
        $workflow = $container
            ->workflowService()
            ->workflowForWork(
                user: $authenticatedUser,
                workId: $selectedWork->id(),
            );
    } catch (\Throwable $exception) {
        if ($error === null) {
            $error = $exception->getMessage();
        }
    }
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
        Workflow Test | Sonic Foundry
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >

    <style>
        .workflow-test-page {
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

        .workflow-test-container {
            width: min(100%, 1000px);
            margin-inline: auto;
        }

        .workflow-test-card {
            margin-bottom: var(--space-6);
            padding: var(--space-8);

            background: var(--color-panel);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
        }

        .workflow-test-form {
            display: grid;
            gap: var(--space-5);
        }

        .workflow-test-form select {
            width: 100%;
            padding: var(--space-3);

            color: var(--color-text);
            background: var(--color-panel-raised);

            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
        }

        .workflow-test-actions {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-4);
        }

        .workflow-list {
            display: grid;
            gap: var(--space-3);

            margin: 0;
            padding: 0;

            list-style: none;
        }

        .workflow-item {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                auto;
            align-items: center;
            gap: var(--space-4);

            padding: var(--space-4);

            background: rgba(255, 255, 255, 0.018);
            border: 1px solid var(--color-border-muted);
            border-radius: var(--radius-md);
        }

        .workflow-item strong {
            color: var(--color-silver);
        }

        .workflow-item span {
            color: var(--color-text-muted);

            font-size: var(--font-size-xs);
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .workflow-item--available {
            border-left: 3px solid var(--color-warning);
        }

        .workflow-item--completed {
            border-left:
                3px solid
                var(--color-success, #9ed59e);
        }

        .workflow-error {
            border-left: 3px solid var(--color-error);
        }

        .workflow-success {
            border-left:
                3px solid
                var(--color-success, #9ed59e);
        }
    </style>
</head>

<body>
    <main class="workflow-test-page">
        <div class="workflow-test-container">
            <section class="workflow-test-card">
                <p class="eyebrow">
                    Workflow Diagnostic
                </p>

                <h1>
                    Pillar Completion and Unlocks
                </h1>

                <p>
                    Story may be completed only when its saved progress
                    evaluation is ready.
                </p>
            </section>

            <section class="workflow-test-card">
                <form
                    class="workflow-test-form"
                    method="post"
                    action="/dev/workflow-test.php"
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

                    <div class="workflow-test-actions">
                        <button
                            class="button button--primary"
                            type="submit"
                        >
                            Complete Story
                        </button>

                        <a
                            class="button button--secondary"
                            href="/dev/workflow-test.php"
                        >
                            Refresh
                        </a>

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
                        workflow-test-card
                        workflow-error
                    "
                    role="alert"
                >
                    <h2>
                        Workflow Failed
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

            <?php if ($success !== null): ?>
                <section
                    class="
                        workflow-test-card
                        workflow-success
                    "
                >
                    <h2>
                        Workflow Updated
                    </h2>

                    <p>
                        <?= htmlspecialchars(
                            $success,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                </section>
            <?php endif; ?>

            <?php if ($workflow !== []): ?>
                <section class="workflow-test-card">
                    <h2>
                        Current Pillar Workflow
                    </h2>

                    <ul class="workflow-list">
                        <?php foreach ($workflow as $item): ?>
                            <li
                                class="
                                    workflow-item
                                    workflow-item--<?= htmlspecialchars(
                                        $item->status()->value,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                "
                            >
                                <strong>
                                    <?= htmlspecialchars(
                                        $item->pillar()->label(),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars(
                                        $item->statusLabel(),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>