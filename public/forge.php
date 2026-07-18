<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

//use DomainException;
use SonicFoundry\Auth\Session;

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
        'status' => 'Beginning',
        'available' => true,
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
        'status' => 'Locked',
        'available' => false,
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
        'status' => 'Locked',
        'available' => false,
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
        'status' => 'Locked',
        'available' => false,
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
        'status' => 'Locked',
        'available' => false,
    ],
];

$requestedPillar = mb_strtolower(
    trim(
        (string) ($_GET['pillar'] ?? 'story')
    )
);

if (
    !isset($pillarConfigurations[$requestedPillar])
    || !$pillarConfigurations[$requestedPillar]['available']
) {
    $requestedPillar = 'story';
}

$activePillar = $pillarConfigurations[$requestedPillar];

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
} catch (\DomainException $error) {
    Session::flash(
        'work_error',
        $error->getMessage()
    );

    header('Location: /workspace.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Load persisted Creative Memory
|--------------------------------------------------------------------------
*/

try {
    $pillarMemory = $container
        ->memoryService()
        ->memoryForWork(
            user: $authenticatedUser,
            workId: $work->id(),
            pillarValue: $requestedPillar,
        );

    $memoryView = $container
        ->memoryPresenter()
        ->present($pillarMemory);
} catch (\DomainException $error) {
    Session::flash(
        'work_error',
        $error->getMessage()
    );

    header('Location: /workspace.php');
    exit;
}

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

                            <strong>
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
                        $isActive = (
                            $slug === $requestedPillar
                        );

                        $isAvailable = (
                            $pillarConfiguration['available']
                        );
                        ?>

                        <?php if ($isAvailable): ?>
                            <a
                                class="
                                    forge-pillar-link
                                    <?= $isActive
                                        ? 'forge-pillar-link--active'
                                        : '' ?>
                                "
                                href="/forge.php?work=<?= $work->id() ?>&pillar=<?= htmlspecialchars(
                                    $slug,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= $isActive
                                    ? 'aria-current="step"'
                                    : '' ?>
                            >
                                <span class="forge-pillar-link__marker">
                                    <?= $isActive ? '●' : '○' ?>
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
                            </a>
                        <?php else: ?>
                            <span
                                class="
                                    forge-pillar-link
                                    forge-pillar-link--locked
                                "
                                aria-disabled="true"
                            >
                                <span class="forge-pillar-link__marker">
                                    ○
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
                                    Locked
                                </span>
                            </span>
                        <?php endif; ?>
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

                    <section class="forge-memory__section">
                        <h3>
                            Story Summary
                        </h3>

                        <div
                            class="<?= $memoryView['summary']['hasValue']
                                ? 'forge-memory__value'
                                : 'forge-memory-empty' ?>"
                            data-memory-field="summary"
                        >
                            <p>
                                <?= nl2br(
                                    htmlspecialchars(
                                        (string) $memoryView['summary']['display'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </p>
                        </div>
                    </section>

                    <section class="forge-memory__section">
                        <h3>
                            Themes
                        </h3>

                        <div data-memory-field="themes">
                            <?php if ($memoryView['themes']['hasValues']): ?>
                                <ul class="forge-memory-tags">
                                    <?php foreach (
                                        $memoryView['themes']['values']
                                        as $theme
                                    ): ?>
                                        <li>
                                            <?= htmlspecialchars(
                                                (string) $theme,
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
                                            (string) $memoryView['themes']['display'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="forge-memory__section">
                        <h3>
                            Perspective
                        </h3>

                        <div
                            class="<?= $memoryView['perspective']['hasValue']
                                ? 'forge-memory__value'
                                : 'forge-memory-empty' ?>"
                            data-memory-field="perspective"
                        >
                            <p>
                                <?= htmlspecialchars(
                                    (string) $memoryView['perspective']['display'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>
                        </div>
                    </section>

                    <section class="forge-memory__section">
                        <h3>
                            Core Tension
                        </h3>

                        <div
                            class="<?= $memoryView['coreTension']['hasValue']
                                ? 'forge-memory__value'
                                : 'forge-memory-empty' ?>"
                            data-memory-field="coreTension"
                        >
                            <p>
                                <?= nl2br(
                                    htmlspecialchars(
                                        (string) $memoryView['coreTension']['display'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </p>
                        </div>
                    </section>

                    <section class="forge-memory__section">
                        <h3>
                            Key Subjects
                        </h3>

                        <div data-memory-field="keySubjects">
                            <?php if ($memoryView['keySubjects']['hasValues']): ?>
                                <ul class="forge-memory-tags">
                                    <?php foreach (
                                        $memoryView['keySubjects']['values']
                                        as $subject
                                    ): ?>
                                        <li>
                                            <?= htmlspecialchars(
                                                (string) $subject,
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
                                            (string) $memoryView['keySubjects']['display'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="forge-memory__section">
                        <h3>
                            Listener Takeaway
                        </h3>

                        <div
                            class="<?= $memoryView['listenerTakeaway']['hasValue']
                                ? 'forge-memory__value'
                                : 'forge-memory-empty' ?>"
                            data-memory-field="listenerTakeaway"
                        >
                            <p>
                                <?= nl2br(
                                    htmlspecialchars(
                                        (string) $memoryView['listenerTakeaway']['display'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </p>
                        </div>
                    </section>
                </div>

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