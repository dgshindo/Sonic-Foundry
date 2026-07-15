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
        'login_error',
        'Your form session expired. Please try again.'
    );

    header('Location: /login.php');
    exit;
}

$email = mb_strtolower(
    trim((string) ($_POST['email'] ?? ''))
);

$password = (string) ($_POST['password'] ?? '');

Session::flash(
    'login_email',
    $email
);

try {
    $container->passwordLogin()->login(
        email: $email,
        password: $password,
    );

    header('Location: /workspace.php');
    exit;
} catch (DomainException) {
    /*
     * Deliberately use one generic response so the page does not
     * reveal whether a particular email address exists.
     */
    Session::flash(
        'login_error',
        'The email address or password is incorrect.'
    );

    header('Location: /login.php');
    exit;
} catch (Throwable $error) {
    error_log(
        'Password login failed: '
        . $error->getMessage()
    );

    Session::flash(
        'login_error',
        'Sign-in could not be completed. Please try again.'
    );

    header('Location: /login.php');
    exit;
}