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

/*
|--------------------------------------------------------------------------
| Request method
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$authenticatedUser = $auth->user();

if (!$authenticatedUser) {
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

/*
|--------------------------------------------------------------------------
| Request data
|--------------------------------------------------------------------------
*/

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

$pillar = mb_strtolower(
    trim(
        (string) ($_POST['pillar'] ?? '')
    )
);

$csrfToken = isset($_POST['csrf_token'])
    ? (string) $_POST['csrf_token']
    : null;

/*
|--------------------------------------------------------------------------
| CSRF validation
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Input validation
|--------------------------------------------------------------------------
*/

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

if ($pillar === '') {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'INVALID_PILLAR',

                'message' => (
                    'A valid creative pillar is required.'
                ),
            ],
        ],
        422
    );
}

/*
|--------------------------------------------------------------------------
| Confirm proposed memory
|--------------------------------------------------------------------------
*/
$progress = null;
$progressError = null;
try {
    $memory = $container
        ->memoryService()
        ->confirm(
            user: $authenticatedUser,
            workId: $workId,
            pillarValue: $pillar,
        );

    try {
            $evaluation = $container
                ->pillarProgressEvaluator()
                ->evaluate(
                    user: $authenticatedUser,
                    workId: $workId,
                    pillarValue: $pillar,
                );

            $progress = $container
                ->progressService()
                ->recordEvaluation(
                    user: $authenticatedUser,
                    workId: $workId,
                    pillarValue: $pillar,
                    evaluation: $evaluation,
                );
        } catch (\Throwable $error) {
            error_log(
                sprintf(
                    'Automatic progress evaluation failed for work %d, pillar %s: %s',
                    $workId,
                    $pillar,
                    $error->getMessage()
                )
            );

            $progressError = (
                'The understanding was confirmed, but readiness '
                . 'could not yet be evaluated.'
            );
        }

    /*$memoryView = $container
        ->memoryPresenter()
        ->present($memory);*/

    respondJson([
        'success' => true,

        'data' => [
            'memory' => $container
                ->memoryPresenter()
                ->present($memory),

            'progress' => $progress !== null
                ? $container
                    ->progressPresenter()
                    ->present($progress)
                : null,

            'progressError' =>
                $progressError,
        ],

        'error' => null,
    ]);
} catch (\DomainException $error) {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'MEMORY_CONFIRMATION_FAILED',
                'message' => $error->getMessage(),
            ],
        ],
        422
    );
} catch (\Throwable $error) {
    error_log(
        sprintf(
            'Creative Memory confirmation failed for work %d, pillar %s: %s',
            $workId,
            $pillar,
            $error->getMessage()
        )
    );

    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'MEMORY_CONFIRMATION_ERROR',

                'message' => (
                    'Creative Memory could not be confirmed. '
                    . 'Please try again.'
                ),
            ],
        ],
        500
    );
}