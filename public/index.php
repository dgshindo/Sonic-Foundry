<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

$authenticatedUser = $auth->user();
$isAuthenticated = $authenticatedUser !== null;

$appName = 'Sonic Foundry';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= htmlspecialchars($appName) ?></title>

    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <main class="landing-page">
        <section class="hero">
            <div class="hero__glow" aria-hidden="true"></div>

            <div class="hero__content">
                <img
                    class="hero__logo"
                    src="/assets/images/sonic-foundry-logo.png"
                    alt="Sonic Foundry anvil and soundwave emblem"
                >

                <p class="hero__eyebrow">
                    Creative Album Development Platform
                </p>

                <div class="display-title">
                    Sonic Foundry
                </div>

                <p class="hero__tagline">
                    Define your sound. Forge your identity.
                </p>

                <p class="hero__description">
                    Shape complete albums through story, emotion,
                    identity, sound, and legacy.
                </p>

                <div class="hero__actions">
    <?php if ($isAuthenticated): ?>
        <a
            class="button button--primary"
            href="/new-project.php"
        >
            Begin New Project
        </a>

        <a
            class="button button--secondary"
            href="/workspace.php"
        >
            Continue Project
        </a>
    <?php else: ?>
        <a
            class="button button--primary"
            href="/register.php"
        >
            Create Free Account
        </a>

        <a
            class="button button--secondary"
            href="/login.php"
        >
            Sign In
        </a>
    <?php endif; ?>
</div>
            </div>
        </section>

        <section class="framework">
            <div class="section-heading">
                <p class="section-heading__eyebrow">
                    The Sonic Foundry Framework
                </p>

                <h2>From first idea to lasting body of work</h2>
            </div>

            <img
                class="framework__image"
                src="/assets/images/sonic_foundry_framework.png"
                alt="The Sonic Foundry five-step framework: Story, Emotion, Identity, Sound, and Legacy"
            >
        </section>

        <section class="principles">
            <article class="principle">
                <span>01</span>
                <h3>Story</h3>
                <p>Begin with the world, message, character, or experience behind the work.</p>
            </article>

            <article class="principle">
                <span>02</span>
                <h3>Emotion</h3>
                <p>Define what the listener should feel and why the work should matter.</p>
            </article>

            <article class="principle">
                <span>03</span>
                <h3>Identity</h3>
                <p>Establish voice, values, imagery, themes, and creative boundaries.</p>
            </article>

            <article class="principle">
                <span>04</span>
                <h3>Sound</h3>
                <p>Transform identity into vocals, instruments, texture, rhythm, and production.</p>
            </article>

            <article class="principle">
                <span>05</span>
                <h3>Legacy</h3>
                <p>Create a cohesive body of work that remains recognizable and meaningful.</p>
            </article>
        </section>

        <footer class="site-footer">
            <p>
                Tools can make noise.
                <strong>Intent creates impact.</strong>
            </p>
        </footer>
    </main>
</body>
</html>