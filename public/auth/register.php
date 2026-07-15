<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$csrfToken = isset($_POST['csrf_token'])
    ? (string) $_POST['csrf_token']
    : null;

if (!Session::verifyCsrfToken($csrfToken)) {
    Session::flash(
        'registration_error',
        'Your form session expired. Please try again.'
    );

    header('Location: /register.php');
    exit;
}

$displayName = trim(
    (string) ($_POST['display_name'] ?? '')
);

$email = trim(
    (string) ($_POST['email'] ?? '')
);

$password = (string) ($_POST['password'] ?? '');

$passwordConfirmation = (string) (
    $_POST['password_confirmation'] ?? ''
);

Session::flash(
    'registration_name',
    $displayName
);

Session::flash(
    'registration_email',
    $email
);

try {
    $container->registration()->register(
        displayName: $displayName,
        email: $email,
        password: $password,
        passwordConfirmation: $passwordConfirmation,
    );

    header('Location: /workspace.php');
    exit;
} catch (DomainException $error) {
    Session::flash(
        'registration_error',
        $error->getMessage()
    );

    header('Location: /register.php');
    exit;
} catch (Throwable $error) {
    error_log(
        'Registration failed: '
        . $error->getMessage()
    );

    Session::flash(
        'registration_error',
        'Your account could not be created. Please try again.'
    );

    header('Location: /register.php');
    exit;
}