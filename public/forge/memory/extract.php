<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3)
    . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;
use SonicFoundry\Memory\PillarMemory;
use SonicFoundry\Work\WorkPillar;

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
function successPayload(
    PillarMemory $memory,
    array $memoryView,
): array {
    return [
        'success' => true,

        'data' => [
            'memory' => $memoryView,

            'proposal' => [
                'id' => $memory->id(),

                'workId' =>
                    $memory->workId(),

                'pillar' => [
                    'value' =>
                        $memory
                            ->pillar()
                            ->value,

                    'label' =>
                        $memory
                            ->pillar()
                            ->label(),
                ],

                'status' => [
                    'value' =>
                        $memory
                            ->status()
                            ->value,

                    'label' =>
                        $memory
                            ->statusLabel(),
                ],

                'revision' =>
                    $memory->revision(),

                'confidence' =>
                    $memory->confidence(),
            ],
        ],

        'error' => null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' =>
                    'METHOD_NOT_ALLOWED',

                'message' =>
                    'A POST request is required.',
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
                'code' =>
                    'AUTHENTICATION_REQUIRED',

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

$pillarValue = mb_strtolower(
    trim(
        (string) (
            $_POST['pillar']
            ?? ''
        )
    )
);

if ($workId === null) {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' => 'INVALID_WORK',

                'message' =>
                    'A valid Work is required.',
            ],
        ],
        422
    );
}

$pillar = WorkPillar::tryFrom(
    $pillarValue
);

if ($pillar === null) {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' =>
                    'INVALID_PILLAR',

                'message' => (
                    'A valid creative pillar '
                    . 'is required.'
                ),
            ],
        ],
        422
    );
}

try {
    /*
    |--------------------------------------------------------------------------
    | Verify workflow access
    |--------------------------------------------------------------------------
    */

    $pillarWorkflow = $container
        ->workflowService()
        ->pillarForWork(
            user: $authenticatedUser,
            workId: $workId,
            pillarValue: $pillar->value,
        );

    if ($pillarWorkflow->isLocked()) {
        throw new \DomainException(
            'That creative pillar is currently locked.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Extract pillar-specific memory
    |--------------------------------------------------------------------------
    |
    | Version 1 uses explicit dispatch. Sound and Impact can be added here
    | when their specialist extractors are created.
    |
    */

    $extraction = match ($pillar) {
        WorkPillar::Story =>
            $container
                ->storyMemoryExtractor()
                ->extract(
                    user:
                        $authenticatedUser,

                    workId:
                        $workId,
                ),

        WorkPillar::Emotion =>
            $container
                ->emotionMemoryExtractor()
                ->extract(
                    user:
                        $authenticatedUser,

                    workId:
                        $workId,
                ),

        WorkPillar::Identity =>
            $container
                ->identityMemoryExtractor()
                ->extract(
                    user:
                        $authenticatedUser,

                    workId:
                        $workId,
                ),

        WorkPillar::Sound =>
            $container
                ->soundMemoryExtractor()
                ->extract(
                    user: $authenticatedUser,
                    workId: $workId,
                ),

        WorkPillar::Impact =>
            $container
                ->impactMemoryExtractor()
                ->extract(
                    user: $authenticatedUser,
                    workId: $workId,
                ),

        default =>
            throw new \DomainException(
                'Memory extraction is not yet '
                . 'available for '
                . $pillar->label()
                . '.'
            ),
    };

    if ($extraction->isEmpty()) {
        throw new \DomainException(
            'The conversation does not yet contain '
            . 'enough established understanding '
            . 'to propose Creative Memory.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Save proposed memory
    |--------------------------------------------------------------------------
    */

    $memory = $container
        ->memoryService()
        ->propose(
            user: $authenticatedUser,
            workId: $workId,
            pillarValue: $pillar->value,
            extraction: $extraction,
        );

    $memoryView = $container
        ->memoryPresenter()
        ->present(
            memory: $memory,
            pillar: $pillar,
        );

    respondJson(
        successPayload(
            memory: $memory,
            memoryView: $memoryView,
        )
    );
} catch (\DomainException $error) {
    respondJson(
        [
            'success' => false,
            'data' => null,

            'error' => [
                'code' =>
                    'MEMORY_EXTRACTION_REJECTED',

                'message' =>
                    $error->getMessage(),
            ],
        ],
        422
    );
} catch (\Throwable $error) {
    error_log(
        sprintf(
            'Forge memory extraction failed '
            . 'for work %d, pillar %s: %s '
            . 'in %s:%d',
            $workId,
            $pillar->value,
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
                    'MEMORY_EXTRACTION_FAILED',

                'message' => (
                    'The Creative Partner could not '
                    . 'propose an understanding. '
                    . 'Your conversation remains safely saved.'
                ),
            ],
        ],
        500
    );
}