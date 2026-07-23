<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3)
    . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;

/**
 * @param array<string, mixed> $payload
 */
function respondJson(
    array $payload,
    int $statusCode = 200,
): never {
    http_response_code($statusCode);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');

    echo json_encode(
        $payload,
        JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'A POST request is required.',
            ],
        ],
        405
    );
}

$authenticatedUser = $auth->user();

if ($authenticatedUser === null) {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'AUTHENTICATION_REQUIRED',

                'message' => (
                    'Your session has expired. '
                    . 'Please sign in again.'
                ),
            ],
        ],
        401
    );
}

$csrfToken = isset($_POST['csrf_token'])
    ? (string) $_POST['csrf_token']
    : null;

if (!Session::verifyCsrfToken($csrfToken)) {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'CSRF_FAILED',

                'message' => (
                    'Your form session expired. '
                    . 'Refresh the Forge and try again.'
                ),
            ],
        ],
        419
    );
}

$submittedWorkId = filter_input(
    INPUT_POST,
    'work_id',
    FILTER_VALIDATE_INT
);

$workId = (
    is_int($submittedWorkId)
    && $submittedWorkId > 0
)
    ? $submittedWorkId
    : null;

if ($workId === null) {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'INVALID_WORK',
                'message' => 'A valid Work is required.',
            ],
        ],
        422
    );
}

try {
    $artifact = $container
        ->styleGuideService()
        ->generateAndSave(
            user: $authenticatedUser,
            workId: $workId,
        );

    respondJson(
        [
            'success' => true,

            'data' => [
                'artifact' => [
                    'id' =>
                        $artifact->id(),

                    'workId' =>
                        $artifact->workId(),

                    'type' => [
                        'value' =>
                            $artifact->type()->value,

                        'label' =>
                            $artifact->typeLabel(),
                    ],

                    'title' =>
                        $artifact->title(),

                    'content' =>
                        $artifact->content(),

                    'revision' =>
                        $artifact->revision(),

                    'createdAt' => [
                        'iso' =>
                            $artifact
                                ->createdAt()
                                ->format(DATE_ATOM),

                        'display' =>
                            $artifact
                                ->createdAt()
                                ->format(
                                    'M j, Y \a\t g:i A'
                                ),
                    ],

                    'updatedAt' => [
                        'iso' =>
                            $artifact
                                ->updatedAt()
                                ->format(DATE_ATOM),

                        'display' =>
                            $artifact
                                ->updatedAt()
                                ->format(
                                    'M j, Y \a\t g:i A'
                                ),
                    ],
                ],
            ],

            'error' => null,
        ]
    );
} catch (\DomainException $error) {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' =>
                    'STYLE_GUIDE_REJECTED',

                'message' =>
                    $error->getMessage(),
            ],
        ],
        422
    );
} catch (\Throwable $error) {
    error_log(
        sprintf(
            'Style Guide generation failed for work %d: %s in %s:%d',
            $workId,
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
        )
    );

    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' =>
                    'STYLE_GUIDE_GENERATION_FAILED',

                'message' => (
                    'The Style Guide could not be generated. '
                    . 'Your confirmed creative work remains safely saved.'
                ),
            ],
        ],
        500
    );
}