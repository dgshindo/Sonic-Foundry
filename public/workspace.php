<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

$authenticatedUser = $auth->requireAuthentication();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>My Works | Sonic Foundry</title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>
<body class="workspace-body">
    <div class="workspace-app">
        <header class="app-header">
            <a
                class="app-brand"
                href="/workspace.php"
                aria-label="Sonic Foundry Workspace"
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
                    class="app-header__link app-header__link--active"
                    href="/workspace.php"
                    aria-current="page"
                >
                    My Works
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
                            $authenticatedUser->firstName(),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <?php if ($authenticatedUser->avatarUrl()): ?>
                        <img
                            class="user-menu__avatar"
                            src="<?= htmlspecialchars(
                                $authenticatedUser->avatarUrl(),
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
                            class="user-menu__avatar user-menu__avatar--fallback"
                            aria-hidden="true"
                        >
                            <?= htmlspecialchars(
                                mb_strtoupper(
                                    mb_substr(
                                        $authenticatedUser->firstName(),
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

        <div class="workspace-layout">
            <aside class="pillar-sidebar">
                <div class="pillar-sidebar__heading">
                    <span class="eyebrow">
                        The Framework
                    </span>

                    <p>
                        Select a work to enter its creative journey.
                    </p>
                </div>

                <nav
                    class="pillar-navigation"
                    aria-label="Creative pillars"
                >
                    <span class="pillar-link pillar-link--disabled">
                        <span class="pillar-link__number">01</span>

                        <span class="pillar-link__content">
                            <strong>Story</strong>
                            <small>What are you saying?</small>
                        </span>
                    </span>

                    <span class="pillar-link pillar-link--disabled">
                        <span class="pillar-link__number">02</span>

                        <span class="pillar-link__content">
                            <strong>Emotion</strong>
                            <small>What should they feel?</small>
                        </span>
                    </span>

                    <span class="pillar-link pillar-link--disabled">
                        <span class="pillar-link__number">03</span>

                        <span class="pillar-link__content">
                            <strong>Identity</strong>
                            <small>Who is speaking?</small>
                        </span>
                    </span>

                    <span class="pillar-link pillar-link--disabled">
                        <span class="pillar-link__number">04</span>

                        <span class="pillar-link__content">
                            <strong>Sound</strong>
                            <small>How does your world sound?</small>
                        </span>
                    </span>

                    <span class="pillar-link pillar-link--disabled">
                        <span class="pillar-link__number">05</span>

                        <span class="pillar-link__content">
                            <strong>Impact</strong>
                            <small>What should remain?</small>
                        </span>
                    </span>
                </nav>
            </aside>

            <main class="works-content">
                <header class="works-header">
                    <div>
                        <p class="eyebrow">
                            Workspace
                        </p>

                        <h1 class="works-title">
                            My Works
                        </h1>

                        <p class="works-introduction">
                            Create, develop, and continue meaningful
                            musical works through the five pillars.
                        </p>
                    </div>

                    <a
                        class="button button--primary"
                        href="/create-work.php"
                    >
                        Create New Work
                    </a>
                </header>

                <section
                    class="works-empty-state"
                    aria-labelledby="empty-state-title"
                >
                    <img
                        class="works-empty-state__logo"
                        src="/assets/images/sonic-foundry-logo.png"
                        alt=""
                    >

                    <p class="eyebrow">
                        The Forge Awaits
                    </p>

                    <h2 id="empty-state-title">
                        Begin your first work
                    </h2>

                    <p>
                        A single, an EP, an album, or something entirely
                        different—every meaningful work begins with intent.
                    </p>

                    <a
                        class="button button--primary button--large"
                        href="/create-work.php"
                    >
                        Create Your First Work
                    </a>
                </section>
            </main>
        </div>
    </div>
</body>
</html>