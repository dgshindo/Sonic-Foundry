<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

$authenticatedUser = $auth->requireAuthentication();

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
        'work_error',
        'Your form session expired. Please try again.'
    );

    header('Location: /create-work.php');
    exit;
}

$workType = trim(
    (string) ($_POST['work_type'] ?? '')
);

$workTitle = trim(
    (string) ($_POST['work_title'] ?? '')
);

Session::flash(
    'work_type',
    $workType
);

Session::flash(
    'work_title',
    $workTitle
);

try {
    $work = $container->workService()->create(
        user: $authenticatedUser,
        title: $workTitle,
        workType: $workType,
    );

    header(
        'Location: /forge.php?work='
        . $work->id()
        . '&pillar=story'
    );

    exit;
} catch (DomainException $error) {
    Session::flash(
        'work_error',
        $error->getMessage()
    );

    header('Location: /create-work.php');
    exit;
} catch (Throwable $error) {
    error_log(
        'Work creation failed: '
        . $error->getMessage()
    );

    Session::flash(
        'work_error',
        'Your work could not be created. Please try again.'
    );

    header('Location: /create-work.php');
    exit;
}