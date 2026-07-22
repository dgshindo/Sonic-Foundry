<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

//use DomainException;
use SonicFoundry\Auth\Session;
use SonicFoundry\Work\WorkPillar;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$authenticatedUser = $auth->requireAuthentication();

/*
|--------------------------------------------------------------------------
| Load and validate the requested Work
|--------------------------------------------------------------------------
*/

$workId = filter_input(
    INPUT_GET,
    'work',
    FILTER_VALIDATE_INT
);

if (!is_int($workId) || $workId < 1) {
    Session::flash(
        'work_error',
        'Select a Work before entering the Forge.'
    );

    header('Location: /workspace.php');
    exit;
}

try {
    $work = $container
        ->workService()
        ->findOwnedWork(
            workId: $workId,
            user: $authenticatedUser,
        );
} catch (\DomainException) {
    Session::flash(
        'work_error',
        'The requested Work could not be found.'
    );

    header('Location: /workspace.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Pillar configuration
|--------------------------------------------------------------------------
|
| Story is presently the only unlocked pillar.
| The remaining pillars will later derive availability from persistent
| progress associated with this Work.
|
*/

$pillarConfigurations = [
    'story' => [
        'number' => '01',
        'name' => 'Story',
        'question' => 'What are you saying?',
        'introduction' => (
            'Before lyrics, melody, or production, '
            . 'there is something worth saying.'
        ),
        'opening_prompt' => (
            'Tell me about the story behind this Work. '
            . 'What made you feel that it needed to exist?'
        ),
        'guide_role' => 'Story Guide',
    ],

    'emotion' => [
        'number' => '02',
        'name' => 'Emotion',
        'question' => 'What should they feel?',
        'introduction' => (
            'Emotion defines the journey your listener experiences.'
        ),
        'opening_prompt' => (
            'How should the listener feel when this Work begins, '
            . 'and how should that feeling change before it ends?'
        ),
        'guide_role' => 'Emotion Guide',
    ],

    'identity' => [
        'number' => '03',
        'name' => 'Identity',
        'question' => 'Who is speaking?',
        'introduction' => (
            'Identity gives the Work its voice, values, '
            . 'and perspective.'
        ),
        'opening_prompt' => (
            'What makes this Work unmistakably yours?'
        ),
        'guide_role' => 'Identity Guide',
    ],

    'sound' => [
        'number' => '04',
        'name' => 'Sound',
        'question' => 'How does your world sound?',
        'introduction' => (
            'Sound transforms your creative identity '
            . 'into an audible world.'
        ),
        'opening_prompt' => (
            'What instruments, textures, rhythms, '
            . 'and production choices belong in this Work?'
        ),
        'guide_role' => 'Sound Guide',
    ],

    'impact' => [
        'number' => '05',
        'name' => 'Impact',
        'question' => 'What should remain?',
        'introduction' => (
            'Impact is what stays with the listener '
            . 'after the music ends.'
        ),
        'opening_prompt' => (
            'When someone finishes this Work, '
            . 'what do you hope has changed for them?'
        ),
        'guide_role' => 'Impact Guide',
    ],
];

/*
|--------------------------------------------------------------------------
| Persistent pillar workflow
|--------------------------------------------------------------------------
*/

try {
    $pillarWorkflow = $container
        ->workflowService()
        ->workflowForWork(
            user: $authenticatedUser,
            workId: $work->id(),
        );

    $workflowView = $container
        ->workflowPresenter()
        ->present($pillarWorkflow);
} catch (\Throwable $error) {
    exit(
        htmlspecialchars(
            $error::class
            . ': '
            . $error->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

/*
|--------------------------------------------------------------------------
| Requested pillar
|--------------------------------------------------------------------------
*/

$requestedPillar = mb_strtolower(
    trim(
        (string) ($_GET['pillar'] ?? 'story')
    )
);

if (!isset($pillarConfigurations[$requestedPillar])) {
    $requestedPillar = 'story';
}

$requestedWorkflow =
    $workflowView[$requestedPillar]
    ?? null;

if (
    $requestedWorkflow === null
    || $requestedWorkflow['isLocked']
) {
    $requestedPillar = 'story';

    $requestedWorkflow =
        $workflowView['story'];
}

$activePillar =
    $pillarConfigurations[$requestedPillar];

$activePillar['status'] =
    $requestedWorkflow['status']['label'];



/*
|--------------------------------------------------------------------------
| Load persisted conversation
|--------------------------------------------------------------------------
*/

try {
    $conversationMessages = $container
        ->conversationService()
        ->messagesForWork(
            user: $authenticatedUser,
            workId: $work->id(),
            pillarValue: $requestedPillar,
        );
} catch (\Throwable $error) {
    throw $error;
    exit(
        htmlspecialchars(
            $error::class
            . ': '
            . $error->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

/*
|--------------------------------------------------------------------------
| Active pillar enum
|--------------------------------------------------------------------------
*/

$requestedPillarEnum =
    \SonicFoundry\Work\WorkPillar::from(
        $requestedPillar
    );

$activeProgressDefinition = $container
    ->pillarRegistry()
    ->definition($requestedPillarEnum)
    ->progress();    

/*
|--------------------------------------------------------------------------
| Persisted pillar memory
|--------------------------------------------------------------------------
*/

$memory = $container
    ->memoryService()
    ->memoryForWork(
        user: $authenticatedUser,
        workId: $work->id(),
        pillarValue: $requestedPillar,
    );

$memoryView = $container
    ->memoryPresenter()
    ->present(
        memory: $memory,
        pillar: $requestedPillarEnum,
    );

/*
|--------------------------------------------------------------------------
| Persisted pillar progress
|--------------------------------------------------------------------------
*/

$progress = $container
    ->progressService()
    ->progressForWork(
        user: $authenticatedUser,
        workId: $work->id(),
        pillarValue: $requestedPillar,
    );

$progressView = $container
    ->progressPresenter()
    ->present($progress);

$currentWorkflow =
    $workflowView[$requestedPillar];

$canCompletePillar = (
    $progressView['exists']
    && $progressView['isReady']
    && $currentWorkflow['isAvailable']
);

/*
|--------------------------------------------------------------------------
| View data
|--------------------------------------------------------------------------
*/

$forgeError = Session::getFlash(
    'forge_error'
);

$oldForgeMessage = Session::getFlash(
    'forge_message',
    ''
);

$firstName = $authenticatedUser->firstName();
$avatarUrl = $authenticatedUser->avatarUrl();

$workTitle = $work->title();
$workType = $work->typeLabel();
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
            $activePillar['name'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
        |
        <?= htmlspecialchars(
            $workTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
        | Sonic Foundry
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>

<body class="forge-body">
    <div class="forge-app">

        <!--
        |--------------------------------------------------------------------------
        | Authenticated application header
        |--------------------------------------------------------------------------
        -->

        <header class="app-header">
            <a
                class="app-brand"
                href="/workspace.php"
                aria-label="Sonic Foundry My Works"
            >
                <img
                    class="app-brand__logo"
                    src="/assets/images/sonic-foundry-logo.png"
                    alt=""
                >

                <span class="app-brand__name">
                    Sonic Foundry
                </span>
            </a>

            <nav
                class="app-header__navigation"
                aria-label="Primary navigation"
            >
                <a
                    class="app-header__link"
                    href="/workspace.php"
                >
                    My Works
                </a>

                <a
                    class="
                        app-header__link
                        app-header__link--active
                    "
                    href="/forge.php?work=<?= $work->id() ?>&pillar=story"
                    aria-current="page"
                >
                    The Forge
                </a>

                <a
                    class="app-header__link"
                    href="#"
                >
                    Library
                </a>
            </nav>

            <div class="user-menu">
                <div class="user-menu__identity">
                    <span class="user-menu__name">
                        <?= htmlspecialchars(
                            $firstName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <?php if (
                        is_string($avatarUrl)
                        && $avatarUrl !== ''
                    ): ?>
                        <img
                            class="user-menu__avatar"
                            src="<?= htmlspecialchars(
                                $avatarUrl,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $authenticatedUser->displayName(),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?> profile picture"
                            referrerpolicy="no-referrer"
                        >
                    <?php else: ?>
                        <span
                            class="
                                user-menu__avatar
                                user-menu__avatar--fallback
                            "
                            aria-hidden="true"
                        >
                            <?= htmlspecialchars(
                                mb_strtoupper(
                                    mb_substr(
                                        $firstName,
                                        0,
                                        1
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <a
                    class="
                        button
                        button--ghost
                        button--small
                    "
                    href="/logout.php"
                >
                    Sign Out
                </a>
            </div>
        </header>

        <!--
        |--------------------------------------------------------------------------
        | Current Work and active workflow state
        |--------------------------------------------------------------------------
        -->

        <section class="forge-project-bar">
            <div class="forge-project-bar__identity">
                <div class="forge-project-bar__meta-row">
                    <span class="eyebrow">
                        Current Work
                    </span>

                    <span class="forge-project-bar__type">
                        <?= htmlspecialchars(
                            $workType,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <span class="forge-project-bar__introduction">
                        <?= htmlspecialchars(
                            $activePillar['introduction'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>
                </div>

                <div class="forge-project-bar__main-row">
                    <h1 class="forge-project-bar__title">
                        <?= htmlspecialchars(
                            $workTitle,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h1>

                    <div class="forge-project-bar__workflow">
                        <div class="forge-project-bar__pillar">
                            <span>
                                Pillar
                                <?= htmlspecialchars(
                                    $activePillar['number'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <span aria-hidden="true">
                                ●
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $activePillar['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </div>

                        <div class="forge-project-bar__status">
                            <span>
                                Status:
                            </span>

                            <strong id="forge-active-workflow-status">
                                <?= htmlspecialchars(
                                    $activePillar['status'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <a
                class="
                    button
                    button--secondary
                    button--small
                "
                href="/workspace.php"
            >
                Return to My Works
            </a>
        </section>

        <!--
        |--------------------------------------------------------------------------
        | Collapsible panel controls
        |--------------------------------------------------------------------------
        -->

        <div
            class="forge-panel-controls"
            aria-label="Forge panel controls"
        >
            <button
                class="forge-panel-toggle"
                id="toggle-pillars"
                type="button"
                aria-controls="forge-sidebar"
                aria-expanded="true"
            >
                <span
                    class="forge-panel-toggle__icon"
                    aria-hidden="true"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </span>

                <span class="forge-panel-toggle__label">
                    Hide Pillars
                </span>
            </button>

            <button
                class="forge-panel-toggle"
                id="toggle-memory"
                type="button"
                aria-controls="forge-memory"
                aria-expanded="true"
            >
                <span class="forge-panel-toggle__label">
                    Hide Memory
                </span>

                <span
                    class="
                        forge-panel-toggle__icon
                        forge-panel-toggle__icon--memory
                    "
                    aria-hidden="true"
                >
                    <span></span>
                    <span></span>
                </span>
            </button>
        </div>

        <!--
        |--------------------------------------------------------------------------
        | Main Forge workspace
        |--------------------------------------------------------------------------
        -->

        <div
            class="forge-layout"
            id="forge-layout"
        >

            <!-- Five Pillars -->

            <aside
                class="forge-sidebar"
                id="forge-sidebar"
            >
                <div class="forge-sidebar__heading">
                    <p class="eyebrow">
                        The Five Pillars
                    </p>

                    <p>
                        Develop the Work deliberately, one creative
                        foundation at a time.
                    </p>
                </div>

                <nav
                    class="forge-pillar-navigation"
                    aria-label="Creative pillars"
                >
                    <?php foreach (
                        $pillarConfigurations
                        as $slug => $pillarConfiguration
                    ): ?>
                        <?php
                        $pillarWorkflow =
                            $workflowView[$slug];

                        $isActive = (
                            $slug === $requestedPillar
                        );

                        $isLocked =
                            $pillarWorkflow['isLocked'];

                        $isCompleted =
                            $pillarWorkflow['isCompleted'];

                        $pillarHref = (
                            '/forge.php?work='
                            . $work->id()
                            . '&pillar='
                            . rawurlencode($slug)
                        );
                        ?>

                        <a
                            class="
                                forge-pillar-link
                                forge-pillar-link--<?= htmlspecialchars(
                                    (string) $pillarWorkflow['status']['value'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                                <?= $isActive
                                    ? 'forge-pillar-link--active'
                                    : '' ?>
                            "
                            data-pillar-link
                            data-pillar="<?= htmlspecialchars(
                                $slug,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            data-work-id="<?= $work->id() ?>"
                            <?= !$isLocked
                                ? 'href="'
                                    . htmlspecialchars(
                                        $pillarHref,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    . '"'
                                : '' ?>
                            <?= $isActive
                                ? 'aria-current="step"'
                                : '' ?>
                            <?= $isLocked
                                ? 'aria-disabled="true" tabindex="-1"'
                                : '' ?>
                        >
                            <span class="forge-pillar-link__marker">
                                <?php if ($isCompleted): ?>
                                    ✓
                                <?php elseif ($isActive): ?>
                                    ●
                                <?php else: ?>
                                    ○
                                <?php endif; ?>
                            </span>

                            <span class="forge-pillar-link__content">
                                <strong>
                                    <?= htmlspecialchars(
                                        $pillarConfiguration['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        $pillarConfiguration['question'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>
                            </span>

                            <span class="forge-pillar-link__status">
                                <?= htmlspecialchars(
                                    (string) $pillarWorkflow['status']['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <!-- Conversation -->

            <main class="forge-conversation">
                <section
                    class="forge-message-history"
                    id="forge-message-history"
                    aria-label="Creative Partner conversation"
                    aria-live="polite"
                >
                    <div
                        class="form-alert form-alert--error"
                        id="forge-error"
                        role="alert"
                        <?= $forgeError ? '' : 'hidden' ?>
                    >
                        <?= $forgeError
                            ? htmlspecialchars(
                                (string) $forgeError,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : '' ?>
                    </div>

                    <article
                        class="
                            forge-message
                            forge-message--partner
                        "
                    >
                        <header class="forge-message__header">
                            <span class="forge-message__identity">
                                Creative Partner
                            </span>

                            <span class="forge-message__role">
                                <?= htmlspecialchars(
                                    $activePillar['guide_role'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </header>

                        <div class="forge-message__body">
                            <p>
                                <?= htmlspecialchars(
                                    $activePillar['opening_prompt'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>
                        </div>
                    </article>

                    <?php if ($conversationMessages === []): ?>
                        <div
                            class="forge-conversation-empty"
                            id="forge-conversation-empty"
                        >
                            <p>
                                Your conversation will remain with this
                                Work as its creative direction develops.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach (
                            $conversationMessages
                            as $message
                        ): ?>
                            <?php
                            $isUserMessage =
                                $message->isUserMessage();
                            ?>

                            <article
                                class="
                                    forge-message
                                    <?= $isUserMessage
                                        ? 'forge-message--user'
                                        : 'forge-message--partner' ?>
                                "
                                data-message-id="<?= $message->id() ?>"
                            >
                                <header class="forge-message__header">
                                    <span class="forge-message__identity">
                                        <?= $isUserMessage
                                            ? htmlspecialchars(
                                                $firstName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : 'Creative Partner' ?>
                                    </span>

                                    <time
                                        class="forge-message__role"
                                        datetime="<?= htmlspecialchars(
                                            $message
                                                ->createdAt()
                                                ->format(DATE_ATOM),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $message
                                                ->createdAt()
                                                ->format(
                                                    'M j, g:i A'
                                                ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </time>
                                </header>

                                <div class="forge-message__body">
                                    <p><?= htmlspecialchars(
                                        $message->content(),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <!-- Composer -->

                <form
                    class="forge-composer"
                    id="forge-composer"
                    method="post"
                    action="/forge/message.php"
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

                    <input
                        type="hidden"
                        name="work_id"
                        value="<?= $work->id() ?>"
                    >

                    <input
                        type="hidden"
                        name="pillar"
                        value="<?= htmlspecialchars(
                            $requestedPillar,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <label for="forge_message">
                        Reply to the Creative Partner
                    </label>

                    <textarea
                        id="forge_message"
                        name="message"
                        rows="2"
                        maxlength="6000"
                        placeholder="Tell the Creative Partner what is behind this Work..."
                        required
                    ><?= htmlspecialchars(
                        (string) $oldForgeMessage,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                    <footer class="forge-composer__footer">
                        <span
                            class="forge-composer__notice"
                            id="forge-composer-notice"
                        >
                            Your message will be saved to this Work.
                        </span>

                        <button
                            class="button button--primary"
                            id="forge-send-button"
                            type="submit"
                        >
                            Send
                        </button>
                    </footer>
                </form>
            </main>

            <!-- Creative Memory -->

            <aside
                class="forge-memory"
                id="forge-memory"
                data-memory-status="<?= htmlspecialchars(
                    (string) $memoryView['status']['value'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
                <header class="forge-memory__header">
                    <div class="forge-memory__header-row">
                        <div>
                            <p class="eyebrow">
                                Creative Memory
                            </p>

                            <h2>
                                What the Forge understands
                            </h2>
                        </div>

                        <span
                            class="
                                forge-memory__status
                                forge-memory__status--<?= htmlspecialchars(
                                    (string) $memoryView['status']['value'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            "
                            id="forge-memory-status"
                        >
                            <?= htmlspecialchars(
                                (string) $memoryView['status']['label'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </div>

                    <p>
                        Conversation explores possibilities.
                        Creative Memory records the understanding
                        you have approved.
                    </p>
                </header>

                <div
                    class="forge-memory__content"
                    id="forge-memory-content"
                >
                    <section class="forge-memory__section">
                        <h3>
                            Work
                        </h3>

                        <dl class="forge-memory-list">
                            <div>
                                <dt>
                                    Title
                                </dt>

                                <dd>
                                    <?= htmlspecialchars(
                                        $workTitle,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    Type
                                </dt>

                                <dd>
                                    <?= htmlspecialchars(
                                        $workType,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <?php foreach (
    $memoryView['sections'] ?? []
    as $memorySection
): ?>
    <?php
    $sectionType = (string) (
        $memorySection['type']
        ?? 'text'
    );

    $sectionKey = (string) (
        $memorySection['key']
        ?? ''
    );

    $sectionLabel = (string) (
        $memorySection['label']
        ?? 'Creative Memory'
    );

    $sectionValue = is_array(
        $memorySection['value']
        ?? null
    )
        ? $memorySection['value']
        : [];
    ?>

    <section class="forge-memory__section">
        <h3>
            <?= htmlspecialchars(
                $sectionLabel,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h3>

        <div
            data-memory-field="<?= htmlspecialchars(
                $sectionKey,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
            <?php if ($sectionType === 'list'): ?>
                <?php
                $values = is_array(
                    $sectionValue['values']
                    ?? null
                )
                    ? $sectionValue['values']
                    : [];

                $hasValues = (
                    $sectionValue['hasValues']
                    ?? false
                ) === true;
                ?>

                <?php if ($hasValues && $values !== []): ?>
                    <ul class="forge-memory-tags">
                        <?php foreach ($values as $value): ?>
                            <li>
                                <?= htmlspecialchars(
                                    (string) $value,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="forge-memory-empty">
                        <p>
                            <?= htmlspecialchars(
                                (string) (
                                    $sectionValue['display']
                                    ?? 'Not yet established.'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php
                $hasValue = (
                    $sectionValue['hasValue']
                    ?? false
                ) === true;
                ?>

                <div
                    class="<?= $hasValue
                        ? 'forge-memory__value'
                        : 'forge-memory-empty' ?>"
                >
                    <p>
                        <?= nl2br(
                            htmlspecialchars(
                                (string) (
                                    $sectionValue['display']
                                    ?? 'Not yet established.'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ) ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php if (
    ($memoryView['sections'] ?? [])
    === []
): ?>
    <section class="forge-memory__section">
        <div class="forge-memory-empty">
            <p>
                Creative Memory fields for this pillar
                are not yet configured.
            </p>
        </div>
    </section>
<?php endif; ?>

                    <section
                        class="
                            forge-memory__section
                            forge-progress
                        "
                        data-progress-status="<?= htmlspecialchars(
                            (string) $progressView['status']['value'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <div class="forge-progress__heading">
                            <?php if (
                                        in_array(
                                            $requestedPillarEnum,
                                            [
                                                WorkPillar::Story,
                                                WorkPillar::Emotion,
                                                WorkPillar::Sound,
                                                WorkPillar::Identity,
                                                WorkPillar::Impact,
                                            ],
                                            true
                                        )
                                    ): ?>
                            <div>
                                <p class="eyebrow">
                                    <?= htmlspecialchars(
                                        $activeProgressDefinition->title(),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                                <h3>
                                    Foundation Review
                                </h3>
                            </div>

                            <span
                                class="
                                    forge-progress__status
                                    forge-progress__status--<?= htmlspecialchars(
                                        (string) $progressView['status']['value'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                "
                            >
                                <?= htmlspecialchars(
                                    (string) $progressView['status']['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </div>
                        
                        <?php if (
                            $currentWorkflow['isAvailable']
                            && !$currentWorkflow['isCompleted']
                        ): ?>
                            <form
                                class="forge-memory-extract"
                                id="forge-memory-extract-form"
                                method="post"
                                action="/forge/memory/extract.php"
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

                                <input
                                    type="hidden"
                                    name="work_id"
                                    value="<?= $work->id() ?>"
                                >

                                <input
                                    type="hidden"
                                    name="pillar"
                                    value="<?= htmlspecialchars(
                                        $requestedPillar,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                <p>
                                    <?php if (!$memoryView['exists']): ?>
                                        When the conversation has established enough
                                        direction, propose a structured understanding
                                        for review.
                                    <?php elseif ($memoryView['isConfirmed']): ?>
                                        The current understanding is confirmed. Continue
                                        the conversation and propose an updated
                                        understanding when the creative direction evolves.
                                    <?php else: ?>
                                        Review the latest conversation and propose an
                                        updated understanding.
                                    <?php endif; ?>
                                </p>

                                <button
                                    class="button button--secondary"
                                    id="forge-memory-extract-button"
                                    type="submit"
                                >
                                    <?php if (!$memoryView['exists']): ?>
                                        Propose Understanding
                                    <?php elseif ($memoryView['isConfirmed']): ?>
                                        Propose Updated Understanding
                                    <?php else: ?>
                                        Update Proposed Understanding
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($progressView['exists']): ?>
                            <div class="forge-progress__score">
                                <strong>
                                    <?= htmlspecialchars(
                                        (string) $progressView['readinessDisplay'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <span>
                                    <?= $progressView['isReady']
                                        ? 'Ready for creator review'
                                        : htmlspecialchars(
                                            $requestedPillarEnum->label()
                                            . ' is still developing',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                </span>
                            </div>

                            <div
                                class="forge-progress__meter"
                                role="progressbar"
                                aria-label="<?= htmlspecialchars(
                                                $activeProgressDefinition->title(),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="<?= (int) $progressView['readinessScore'] ?>"
                            >
                                <span
                                    style="--forge-progress-value: <?= (int) $progressView['readinessScore'] ?>%;"
                                ></span>
                            </div>

                            <ul class="forge-progress__criteria">
                                <?php foreach (
                                    $progressView['criteria']
                                    as $criterion
                                ): ?>
                                    <li
                                        class="
                                            forge-progress__criterion
                                            forge-progress__criterion--<?= htmlspecialchars(
                                                (string) $criterion['status']['value'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        "
                                    >
                                        <span
                                            class="forge-progress__criterion-symbol"
                                            aria-hidden="true"
                                        >
                                            <?= htmlspecialchars(
                                                (string) $criterion['symbol'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <span class="forge-progress__criterion-label">
                                            <?= htmlspecialchars(
                                                (string) $criterion['label'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <span class="forge-progress__criterion-status">
                                            <?= htmlspecialchars(
                                                (string) $criterion['status']['label'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="forge-progress__recommendation">
                                <strong>
                                    Recommendation
                                </strong>

                                <p>
                                    <?= htmlspecialchars(
                                        (string) $progressView['recommendation']['display'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>
                            </div>
                            <div
    class="forge-workflow-action"
    id="forge-workflow-action"
>
    <?php
$nextPillar = null;

foreach ($workflowView as $pillarKey => $workflow) {
    if (
        !$workflow['isLocked']
        && !$workflow['isCompleted']
        && $pillarKey !== $requestedPillar
    ) {
        $nextPillar = $pillarKey;
        break;
    }
}

$nextPillarWorkflow = (
    $nextPillar !== null
)
    ? (
        $workflowView[
            $requestedPillar
        ]
        ?? null
    )
    : null;
?>

<?php if ($currentWorkflow['isCompleted']): ?>
    <div class="forge-workflow-action__completed">
        <strong>
            <?= htmlspecialchars(
                $requestedPillarEnum->label(),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
            Complete
        </strong>

        <?php if (
            $currentWorkflow['completedAt']
            !== null
        ): ?>
            <span>
                Completed
                <?= htmlspecialchars(
                    (string) $currentWorkflow
                        ['completedAt']
                        ['display'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        <?php endif; ?>
    </div>

   <?php if (
    $nextPillar !== null
    && $nextPillarWorkflow !== null
    && !$nextPillarWorkflow['isLocked']
): ?>
    <?php
    $nextPillarEnum =
        \SonicFoundry\Work\WorkPillar::from(
            $nextPillar
        );
    ?>

    <a
        class="button button--primary"
        id="forge-next-pillar-link"
        href="/forge.php?work=<?= $work->id() ?>&pillar=<?= htmlspecialchars(
            $nextPillar,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >
        Continue to
        <?= htmlspecialchars(
            $nextPillarEnum->label(),
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </a>
<?php endif; ?>

<?php elseif ($canCompletePillar): ?>
    <form
        class="forge-workflow-complete"
        id="forge-workflow-complete-form"
        method="post"
        action="/forge/workflow/complete.php"
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

        <input
            type="hidden"
            name="work_id"
            value="<?= $work->id() ?>"
        >

        <input
            type="hidden"
            name="pillar"
            value="<?= htmlspecialchars(
                $requestedPillar,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <p>
            The
            <?= htmlspecialchars(
                $requestedPillarEnum->label(),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
            foundation is ready. Complete it when this
            understanding is strong enough to guide the
            next creative stage.
        </p>

        <button
            class="button button--primary"
            id="forge-workflow-complete-button"
            type="submit"
        >
            Complete
            <?= htmlspecialchars(
                $requestedPillarEnum->label(),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </button>
    </form>
<?php endif; ?>
</div>

<div
    class="forge-workflow-feedback"
    id="forge-workflow-feedback"
    role="status"
    aria-live="polite"
    hidden
></div>
                            <p class="forge-progress__meta">
                                Evaluation revision
                                <?= (int) $progressView['revision'] ?>
                                ·
                                <?= htmlspecialchars(
                                    (string) $progressView['evaluatedAt']['display'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>
                        <?php else: ?>
                            <div class="forge-progress__empty">
                                <p>
                                    <?= htmlspecialchars(
                                        $requestedPillarEnum->label()
                                        . ' readiness has not yet been evaluated.',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                                <?php if (!$memoryView['isConfirmed']): ?>
                                    <p>
                                        <?php $message = sprintf(
                                            'Confirm the %s understanding before evaluating its foundation.',
                                            $requestedPillarEnum->label()
                                        ); ?>
                                    </p>
                                <?php else: ?>
                                    <p>
                                        <?php $message = sprintf(
                                            'The confirmed %s Memory is ready for a progress evaluation.',
                                            $requestedPillarEnum->label()
                                        ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                </div>

            <?php endif; ?>

                <div
                    class="forge-memory__feedback"
                    id="forge-memory-feedback"
                    role="status"
                    aria-live="polite"
                    hidden
                ></div>

                <?php if ($memoryView['canConfirm']): ?>
                    <form
                        class="forge-memory__confirmation"
                        id="forge-memory-confirm-form"
                        method="post"
                        action="/forge/memory/confirm.php"
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

                        <input
                            type="hidden"
                            name="work_id"
                            value="<?= $work->id() ?>"
                        >

                        <input
                            type="hidden"
                            name="pillar"
                            value="<?= htmlspecialchars(
                                $requestedPillar,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                        <p>
                            This is the Forge's proposed understanding.
                            Confirm it only when it accurately reflects
                            the Work.
                        </p>

                        <button
                            class="button button--primary"
                            id="forge-memory-confirm-button"
                            type="submit"
                        >
                            Confirm Understanding
                        </button>
                    </form>
                <?php endif; ?>
                
                <footer class="forge-memory__footer">
                    <span id="forge-memory-meta">
                        <?php if ($memoryView['exists']): ?>
                            Revision
                            <?= (int) $memoryView['revision'] ?>
                            · Updated
                            <?= htmlspecialchars(
                                (string) $memoryView['updatedAt']['display'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        <?php else: ?>
                            Story understanding has not yet been proposed.
                        <?php endif; ?>
                    </span>
                </footer>
            </aside>
        </div>
    </div>

    <!--
    |--------------------------------------------------------------------------
    | Collapsible panel behavior
    |--------------------------------------------------------------------------
    -->

    <script>
        (() => {
            'use strict';

            const layout = document.getElementById(
                'forge-layout'
            );

            const pillarsButton = document.getElementById(
                'toggle-pillars'
            );

            const memoryButton = document.getElementById(
                'toggle-memory'
            );

            if (
                !layout
                || !pillarsButton
                || !memoryButton
            ) {
                return;
            }

            const storageKeys = {
                pillars:
                    'sonicFoundry.forge.pillarsCollapsed',

                memory:
                    'sonicFoundry.forge.memoryCollapsed'
            };

            const readStoredBoolean = (key) => {
                try {
                    return (
                        localStorage.getItem(key)
                        === 'true'
                    );
                } catch {
                    return false;
                }
            };

            const storeBoolean = (
                key,
                value
            ) => {
                try {
                    localStorage.setItem(
                        key,
                        String(value)
                    );
                } catch {
                    /*
                     * The controls remain functional when browser
                     * storage is unavailable.
                     */
                }
            };

            const updateButton = (
                button,
                expanded,
                expandedText,
                collapsedText
            ) => {
                button.setAttribute(
                    'aria-expanded',
                    expanded ? 'true' : 'false'
                );

                const label = button.querySelector(
                    '.forge-panel-toggle__label'
                );

                if (label) {
                    label.textContent = expanded
                        ? expandedText
                        : collapsedText;
                }
            };

            const setPillarsCollapsed = (
                collapsed
            ) => {
                layout.classList.toggle(
                    'forge-layout--pillars-collapsed',
                    collapsed
                );

                updateButton(
                    pillarsButton,
                    !collapsed,
                    'Hide Pillars',
                    'Show Pillars'
                );
            };

            const setMemoryCollapsed = (
                collapsed
            ) => {
                layout.classList.toggle(
                    'forge-layout--memory-collapsed',
                    collapsed
                );

                updateButton(
                    memoryButton,
                    !collapsed,
                    'Hide Memory',
                    'Show Memory'
                );
            };

            pillarsButton.addEventListener(
                'click',
                () => {
                    const collapsed =
                        !layout.classList.contains(
                            'forge-layout--pillars-collapsed'
                        );

                    setPillarsCollapsed(collapsed);

                    storeBoolean(
                        storageKeys.pillars,
                        collapsed
                    );
                }
            );

            memoryButton.addEventListener(
                'click',
                () => {
                    const collapsed =
                        !layout.classList.contains(
                            'forge-layout--memory-collapsed'
                        );

                    setMemoryCollapsed(collapsed);

                    storeBoolean(
                        storageKeys.memory,
                        collapsed
                    );
                }
            );

            setPillarsCollapsed(
                readStoredBoolean(
                    storageKeys.pillars
                )
            );

            setMemoryCollapsed(
                readStoredBoolean(
                    storageKeys.memory
                )
            );
        })();
    </script>

    <!--
    |--------------------------------------------------------------------------
    | Asynchronous conversation behavior
    |--------------------------------------------------------------------------
    -->

    <script
        src="/assets/js/forge.js"
        defer
    ></script>
</body>
</html>