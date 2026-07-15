<?php
declare(strict_types=1);

$services = require dirname(__DIR__) . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

if ($auth->check()) {
    header('Location: /workspace.php');
    exit;
}

$error = Session::getFlash('login_error')
    ?? Session::getFlash('auth_error');

$oldEmail = Session::getFlash(
    'login_email',
    ''
);

$googleClientId = env('GOOGLE_CLIENT_ID');

if (!$googleClientId) {
    throw new RuntimeException(
        'GOOGLE_CLIENT_ID is not configured.'
    );
}

$error = Session::getFlash('auth_error');

$googleClientId = env('GOOGLE_CLIENT_ID');

if (!$googleClientId) {
    throw new RuntimeException(
        'GOOGLE_CLIENT_ID is not configured.'
    );
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

    <title>Enter the Forge | Sonic Foundry</title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >

    <script
        src="https://accounts.google.com/gsi/client"
        async
        defer
    ></script>
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
                    Authentication
                </div>

                <div class="display-title display-title--small">
                    Enter the Forge
                </div>

                <p class="lead">
                    Continue shaping your sound and legacy.
                </p>

            </div>

            <?php if ($error): ?>
                <p class="status">
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            <?php endif; ?>

            
            <div class="google-signin">
                <div class="google-signin-panel">
                    <div
                        id="google-signin-button"
                        aria-label="Sign in with Google"
                    ></div>

                    <p
                        id="google-auth-error"
                        role="alert"
                        hidden
                    ></p>
                </div>
            </div>

            <div class="auth-divider">
    <span>or sign in with email</span>
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
    action="/auth/password.php"
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
            autocomplete="current-password"
            required
        >
    </div>

    <button
        class="button button--primary auth-submit"
        type="submit"
    >
        Sign In
    </button>
</form>

<div class="auth-footer">
    <p>
        New to Sonic Foundry?
        <a href="/register.php">
            Create a free account
        </a>
    </p>

    <a href="/">
        Return to Sonic Foundry
    </a>
</div>

            <?php if (env('APP_ENV') === 'local'): ?>
                <div class="hero__actions">
                    <a
                        class="button button--secondary"
                        href="/development-login.php"
                    >
                        Development Login
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </main>

<script>
    window.addEventListener('load', () => {
        const errorElement = document.getElementById(
            'google-auth-error'
        );

        google.accounts.id.initialize({
            client_id: <?= json_encode(
                $googleClientId,
                JSON_THROW_ON_ERROR
            ) ?>,
            callback: async (response) => {
                errorElement.hidden = true;
                errorElement.textContent = '';

                const body = new URLSearchParams({
                    credential: response.credential
                });

                try {
                    const result = await fetch(
                        '/auth/google.php',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type':
                                    'application/x-www-form-urlencoded'
                            },
                            body: body.toString(),
                            credentials: 'same-origin'
                        }
                    );

                    const payload = await result.json();

                    if (
                        !result.ok ||
                        !payload.success
                    ) {
                        throw new Error(
                            payload.error?.message ??
                            'Google sign-in failed.'
                        );
                    }

                    window.location.href =
                        payload.data.redirect;
                } catch (error) {
                    errorElement.textContent =
                        error.message;

                    errorElement.hidden = false;
                }
            }
        });

        google.accounts.id.renderButton(
            document.getElementById(
                'google-signin-button'
            ),
            {
                type: 'standard',
                theme: 'filled_black',
                size: 'large',
                text: 'continue_with',
                shape: 'rectangular',
                logo_alignment: 'left',
                width: 320
            }
        );
    });
</script>
    
</body>
</html>