<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

$authenticatedUser = $auth->requireAuthentication();

$workId = filter_input(
    INPUT_GET,
    'work',
    FILTER_VALIDATE_INT
);

if (!$workId) {
    Session::flash(
        'work_error',
        'Select a work before entering the Forge.'
    );

    header('Location: /workspace.php');
    exit;
}

try {
    $work = $container->workService()->findOwnedWork(
        workId: $workId,
        user: $authenticatedUser,
    );
} catch (DomainException) {
    http_response_code(404);

    Session::flash(
        'work_error',
        'The requested work could not be found.'
    );

    header('Location: /workspace.php');
    exit;
}

$workTitle = $work->title();
$workType = $work->typeLabel();

$requestedPillar = mb_strtolower(
    trim((string) ($_GET['pillar'] ?? 'story'))
);

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
            'Tell me about the story behind this work. '
            . 'What made you feel that it needed to exist?'
        ),
    ],
    'emotion' => [
        'number' => '02',
        'name' => 'Emotion',
        'question' => 'What should they feel?',
        'introduction' => (
            'Emotion defines the journey your listener experiences.'
        ),
        'opening_prompt' => (
            'How should the listener feel when this work begins, '
            . 'and how should that feeling change before it ends?'
        ),
    ],
    'identity' => [
        'number' => '03',
        'name' => 'Identity',
        'question' => 'Who is speaking?',
        'introduction' => (
            'Identity gives the work its voice, values, and perspective.'
        ),
        'opening_prompt' => (
            'What makes this work unmistakably yours?'
        ),
    ],
    'sound' => [
        'number' => '04',
        'name' => 'Sound',
        'question' => 'How does your world sound?',
        'introduction' => (
            'Sound transforms your creative identity into an audible world.'
        ),
        'opening_prompt' => (
            'What instruments, textures, rhythms, and production choices '
            . 'belong in this work?'
        ),
    ],
    'impact' => [
        'number' => '05',
        'name' => 'Impact',
        'question' => 'What should remain?',
        'introduction' => (
            'Impact is what stays with the listener after the music ends.'
        ),
        'opening_prompt' => (
            'When someone finishes this work, '
            . 'what do you hope has changed for them?'
        ),
    ],
];

/*
 * Story is the only available pillar during this visual checkpoint.
 * Later pillars will unlock through approved project state.
 */
if ($requestedPillar !== 'story') {
    $requestedPillar = 'story';
}

$activePillar = $pillarConfigurations[$requestedPillar];

$firstName = $authenticatedUser->firstName();
$avatarUrl = $authenticatedUser->avatarUrl();
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
        | <?= htmlspecialchars(
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
                    class="app-header__link app-header__link--active"
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

                    <?php if ($avatarUrl): ?>
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
                    class="button button--ghost button--small"
                    href="/logout.php"
                >
                    Sign Out
                </a>
            </div>
        </header>

        <section class="forge-project-bar">
            <div class="forge-project-bar__identity">
                <span class="eyebrow">
                    Current Work
                </span>

                <div class="forge-project-bar__title-row">
                    <h1 class="forge-project-bar__title">
                        <?= htmlspecialchars(
                            $workTitle,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h1>

                    <span class="forge-project-bar__type">
                        <?= htmlspecialchars(
                            $workType,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>
                </div>
            </div>

            <a
                class="button button--secondary button--small"
                href="/workspace.php"
            >
                Return to My Works
            </a>
        </section>

        <div class="forge-layout">
            <aside class="forge-sidebar">
                <div class="forge-sidebar__heading">
                    <p class="eyebrow">
                        The Five Pillars
                    </p>

                    <p>
                        Develop the work deliberately, one creative
                        foundation at a time.
                    </p>
                </div>

                <nav
                    class="forge-pillar-navigation"
                    aria-label="Creative pillars"
                >
                    <?php
                    foreach (
                        $pillarConfigurations
                        as $slug => $pillarConfiguration
                    ):
                        $isActive = $slug === $requestedPillar;
                        $isAvailable = $slug === 'story';
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

                                <span
                                    class="forge-pillar-link__status"
                                    aria-label="Locked"
                                >
                                    Locked
                                </span>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <main class="forge-conversation">
                <header class="forge-conversation__header">
                    <div>
                        <p class="eyebrow">
                            Pillar
                            <?= htmlspecialchars(
                                $activePillar['number'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <h2 class="forge-conversation__title">
                            <?= htmlspecialchars(
                                $activePillar['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h2>

                        <p class="forge-conversation__introduction">
                            <?= htmlspecialchars(
                                $activePillar['introduction'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    </div>

                    <div class="forge-pillar-status">
                        <span class="forge-pillar-status__label">
                            Status
                        </span>

                        <strong>
                            Beginning
                        </strong>
                    </div>
                </header>

                <section
                    class="forge-message-history"
                    aria-label="Creative Partner conversation"
                    aria-live="polite"
                >
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
                                Story Guide
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

                    <div class="forge-conversation-empty">
                        <p>
                            Your conversation will remain with this work
                            as its creative direction develops.
                        </p>
                    </div>
                </section>

                <form
                    class="forge-composer"
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
                        name="pillar"
                        value="<?= htmlspecialchars(
                            $requestedPillar,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <label
                        class="sr-only"
                        for="forge_message"
                    >
                        Reply to the Creative Partner
                    </label>

                    <textarea
                        id="forge_message"
                        name="message"
                        rows="4"
                        maxlength="6000"
                        placeholder="Tell the Creative Partner what is behind this work..."
                        disabled
                    ></textarea>

                    <footer class="forge-composer__footer">
                        <span class="forge-composer__notice">
                            Conversation will be connected in the next
                            Forge checkpoint.
                        </span>

                        <button
                            class="button button--primary"
                            type="submit"
                            disabled
                        >
                            Send
                        </button>
                    </footer>
                </form>
            </main>

            <aside class="forge-memory">
                <header class="forge-memory__header">
                    <p class="eyebrow">
                        Creative Memory
                    </p>

                    <h2>
                        What the Forge understands
                    </h2>

                    <p>
                        Confirmed decisions will appear here.
                        Possibilities discussed in conversation remain
                        separate until approved.
                    </p>
                </header>

                <section class="forge-memory__section">
                    <h3>
                        Work
                    </h3>

                    <dl class="forge-memory-list">
                        <div>
                            <dt>Title</dt>

                            <dd>
                                <?= htmlspecialchars(
                                    $workTitle,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </dd>
                        </div>

                        <div>
                            <dt>Type</dt>

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

                    <div class="forge-memory-empty">
                        <p>
                            No Story summary has been confirmed yet.
                        </p>
                    </div>
                </section>

                <section class="forge-memory__section">
                    <h3>
                        Emerging Themes
                    </h3>

                    <div class="forge-memory-empty">
                        <p>
                            Themes will appear after they are discussed
                            and approved.
                        </p>
                    </div>
                </section>

                <section class="forge-memory__section">
                    <h3>
                        Perspective</h3>

                    <div class="forge-memory-empty">
                        <p>
                            The narrative perspective has not yet been
                            established.
                        </p>
                    </div>
                </section>

                <footer class="forge-memory__footer">
                    <span>
                        Story is not yet complete.
                    </span>
                </footer>
            </aside>
        </div>
    </div>
</body>
</html>