<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

if ($auth->check()) {
    header('Location: /workspace.php');
    exit;
}

$error = Session::getFlash('registration_error');

$oldName = Session::getFlash(
    'registration_name',
    ''
);

$oldEmail = Session::getFlash(
    'registration_email',
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

    <title>Create Account | Sonic Foundry</title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>
<body>
    <main class="app-shell">
        <section class="welcome-panel">
            <div class="auth-header">
                <a href="/" class="auth-logo-link" aria-label="Sonic Foundry Home">
                    <img
                        class="auth-logo"
                        src="/assets/images/sonic-foundry-logo.png"
                        alt="Sonic Foundry anvil and soundwave emblem"
                    >
                </a>
                <div class="eyebrow">
                    Free Creator Account
                </div>

                <div class="display-title display-title--small">
                    Create Your Account
                </div>

                <p class="auth-introduction">
                    Enter the Foundry and begin shaping your
                    first creative project.
                </p>
            </div>
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
                class="auth-form"
                method="post"
                action="/auth/register.php"
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

                <div class="form-field">
                    <label for="display_name">
                        Display name
                    </label>

                    <input
                        id="display_name"
                        name="display_name"
                        type="text"
                        maxlength="150"
                        autocomplete="name"
                        required
                        value="<?= htmlspecialchars(
                            (string) $oldName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="email">
                        Email address
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        maxlength="255"
                        autocomplete="email"
                        required
                        value="<?= htmlspecialchars(
                            (string) $oldEmail,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="password">
                        Password
                    </label>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >

                    <p class="field-help">
                        Use at least 12 characters.
                    </p>
                </div>

                <div class="form-field">
                    <label for="password_confirmation">
                        Confirm password
                    </label>

                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        minlength="12"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button
                    class="button button--primary auth-submit"
                    type="submit"
                >
                    Create Free Account
                </button>
            </form>

            <div class="auth-footer">
                <p>
                    Already have an account?
                    <a href="/login.php">
                        Sign in
                    </a>
                </p>

                <a href="/">
                    Return to Sonic Foundry
                </a>
            </div>
        </section>
    </main>
</body>
</html>