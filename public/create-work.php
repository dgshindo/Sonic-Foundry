<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

$authenticatedUser = $auth->requireAuthentication();

$error = Session::getFlash('work_error');

$oldType = Session::getFlash(
    'work_type',
    'single'
);

$oldTitle = Session::getFlash(
    'work_title',
    ''
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Create New Work | Sonic Foundry</title>

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
                            class="
                                user-menu__avatar
                                user-menu__avatar--fallback
                            "
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
                        Your work will move through five
                        intentional creative stages.
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

            <main class="create-work-content">
                <header class="create-work-header">
                    <a
                        class="create-work-back-link"
                        href="/workspace.php"
                    >
                        ← Return to My Works
                    </a>

                    <p class="eyebrow">
                        New Work
                    </p>

                    <h1 class="create-work-title">
                        What are you creating?
                    </h1>

                    <p class="create-work-introduction">
                        Choose the form your work will take.
                        The Forge will adapt its guidance to match.
                    </p>
                </header>

                <?php if ($error): ?>
                    <div
                        class="form-alert form-alert--error"
                        role="alert"
                    >
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                <?php endif; ?>

                <form
                    class="create-work-form"
                    method="post"
                    action="/works/create.php"
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

                    <fieldset class="work-type-fieldset">
                        <legend class="sr-only">
                            Select the type of musical work
                        </legend>

                        <div class="work-type-grid">
                            <?php
                            $workTypes = [
                                'single' => [
                                    'name' => 'Single',
                                    'description' =>
                                        'One focused musical statement.',
                                    'symbol' => 'Ⅰ',
                                ],
                                'ep' => [
                                    'name' => 'EP',
                                    'description' =>
                                        'A concise collection with a shared identity.',
                                    'symbol' => 'Ⅲ',
                                ],
                                'album' => [
                                    'name' => 'Album',
                                    'description' =>
                                        'A complete body of connected musical work.',
                                    'symbol' => 'Ⅴ',
                                ],
                                'soundtrack' => [
                                    'name' => 'Soundtrack',
                                    'description' =>
                                        'Music created to support a world or narrative.',
                                    'symbol' => '◈',
                                ],
                                'score' => [
                                    'name' => 'Score',
                                    'description' =>
                                        'Music composed around scenes, movement, or story.',
                                    'symbol' => '♩',
                                ],
                                'other' => [
                                    'name' => 'Other',
                                    'description' =>
                                        'A musical work that follows its own form.',
                                    'symbol' => '✦',
                                ],
                            ];
                            ?>

                            <?php foreach ($workTypes as $value => $type): ?>
                                <label class="work-type-option">
                                    <input
                                        class="work-type-option__input"
                                        type="radio"
                                        name="work_type"
                                        value="<?= htmlspecialchars(
                                            $value,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?= $oldType === $value
                                            ? 'checked'
                                            : '' ?>
                                    >

                                    <span class="work-type-option__card">
                                        

                                        <span class="work-type-option__name">
                                            <?= htmlspecialchars(
                                                $type['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <span
                                            class="work-type-option__description"
                                        >
                                            <?= htmlspecialchars(
                                                $type['description'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <span
                                            class="work-type-option__indicator"
                                            aria-hidden="true"
                                        ></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <section class="work-name-section">
                        <div>
                            <p class="eyebrow">
                                Working Title
                            </p>

                            <h2>
                                What shall we call it?
                            </h2>

                            <p>
                                This may be changed later. An untitled work
                                is perfectly acceptable at the beginning.
                            </p>
                        </div>

                        <div class="form-field">
                            <label for="work_title">
                                Work title
                            </label>

                            <input
                                id="work_title"
                                name="work_title"
                                type="text"
                                maxlength="180"
                                autocomplete="off"
                                placeholder="Untitled Work"
                                value="<?= htmlspecialchars(
                                    (string) $oldTitle,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>
                    </section>

                    <footer class="create-work-actions">
                        <a
                            class="button button--secondary"
                            href="/workspace.php"
                        >
                            Cancel
                        </a>

                        <button
                            class="button button--primary button--large"
                            type="submit"
                        >
                            Begin Forging
                        </button>
                    </footer>
                </form>
            </main>
        </div>
    </div>
</body>
</html>