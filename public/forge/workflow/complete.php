<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3)
    . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;
use SonicFoundry\Workflow\PillarWorkflow;

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

/**
 * @return array<string, mixed>
 */
function serializeWorkflow(
    PillarWorkflow $workflow,
): array {
    return [
        'id' => $workflow->id(),

        'workId' => $workflow->workId(),

        'pillar' => [
            'value' => $workflow->pillar()->value,
            'label' => $workflow->pillar()->label(),
        ],

        'status' => [
            'value' => $workflow->status()->value,
            'label' => $workflow->statusLabel(),
        ],

        'isLocked' => $workflow->isLocked(),

        'isAvailable' => $workflow->isAvailable(),

        'isCompleted' => $workflow->isCompleted(),

        'revision' => $workflow->revision(),

        'unlockedAt' => $workflow->unlockedAt() !== null
            ? [
                'iso' => $workflow
                    ->unlockedAt()
                    ?->format(DATE_ATOM),

                'display' => $workflow
                    ->unlockedAt()
                    ?->format('M j, Y \a\t g:i A'),
            ]
            : null,

        'completedAt' => $workflow->completedAt() !== null
            ? [
                'iso' => $workflow
                    ->completedAt()
                    ?->format(DATE_ATOM),

                'display' => $workflow
                    ->completedAt()
                    ?->format('M j, Y \a\t g:i A'),
            ]
            : null,
    ];
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

try {
    $completedWorkflow = $container
        ->workflowService()
        ->complete(
            user: $authenticatedUser,
            workId: $workId,
            pillarValue: $pillar,
        );

    $workflow = $container
        ->workflowService()
        ->workflowForWork(
            user: $authenticatedUser,
            workId: $workId,
        );

    respondJson([
        'success' => true,

        'data' => [
            'completed' => serializeWorkflow(
                $completedWorkflow
            ),

            'workflow' => array_map(
                static fn (
                    PillarWorkflow $item
                ): array => serializeWorkflow($item),
                $workflow
            ),
        ],

        'error' => null,
    ]);
} catch (\DomainException $error) {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'WORKFLOW_COMPLETION_FAILED',
                'message' => $error->getMessage(),
            ],
        ],
        422
    );
} catch (\Throwable $error) {
    error_log(
        'Pillar workflow completion failed: '
        . $error->getMessage()
    );

    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'WORKFLOW_COMPLETION_ERROR',

                'message' => (
                    'The pillar could not be completed. '
                    . 'Please try again.'
                ),
            ],
        ],
        500
    );
}