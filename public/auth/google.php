<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    header('Content-Type: application/json');

    echo json_encode(
        [
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'POST request required.',
            ],
        ],
        JSON_THROW_ON_ERROR
    );

    exit;
}

$credential = trim(
    (string) ($_POST['credential'] ?? '')
);

if ($credential === '') {
    http_response_code(422);

    header('Content-Type: application/json');

    echo json_encode(
        [
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'MISSING_CREDENTIAL',
                'message' => 'Google credential is required.',
            ],
        ],
        JSON_THROW_ON_ERROR
    );

    exit;
}

try {
    $container->googleLogin()->login(
        $credential
    );

    header('Content-Type: application/json');

    echo json_encode(
        [
            'success' => true,
            'data' => [
                'redirect' => '/workspace.php',
            ],
            'error' => null,
        ],
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $error) {
    error_log(
        'Google authentication failed: ' .
        $error->getMessage()
    );

    http_response_code(401);

    header('Content-Type: application/json');

    echo json_encode(
        [
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'GOOGLE_AUTH_FAILED',
                'message' => 'Google sign-in could not be completed.',
            ],
        ],
        JSON_THROW_ON_ERROR
    );
}