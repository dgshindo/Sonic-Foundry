<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/config/bootstrap.php';

use SonicFoundry\Auth\Session;
use SonicFoundry\Work\WorkPillar;

/**
 * Return a conventional JSON error before streaming begins.
 *
 * @param array<string, mixed> $payload
 */
function respondWithJson(
    array $payload,
    int $statusCode,
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
 * Emit one newline-delimited JSON stream event.
 *
 * @param array<string, mixed> $event
 */
function emitStreamEvent(
    array $event,
): void {
    echo json_encode(
        $event,
        JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

    echo "\n";

    if (ob_get_level() > 0) {
        @ob_flush();
    }

    flush();
}

/*
|--------------------------------------------------------------------------
| Request method
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respondWithJson(
        [
            'success' => false,
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
    respondWithJson(
        [
            'success' => false,
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
    respondWithJson(
        [
            'success' => false,
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
| Basic request validation
|--------------------------------------------------------------------------
*/

if ($workId === null) {
    respondWithJson(
        [
            'success' => false,
            'error' => [
                'code' => 'INVALID_WORK',
                'message' => 'A valid Work is required.',
            ],
        ],
        422
    );
}

$resolvedPillar = WorkPillar::tryFrom(
    mb_strtolower(
        trim($pillar)
    )
);

if ($resolvedPillar === null) {
    respondWithJson(
        [
            'success' => false,

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

$pillar = $resolvedPillar->value;

/*
|--------------------------------------------------------------------------
| Verify Work ownership before opening the stream
|--------------------------------------------------------------------------
*/

try {
    $work = $container
        ->workService()
        ->findOwnedWork(
            workId: $workId,
            user: $authenticatedUser,
        );
} catch (\DomainException $error) {
    respondWithJson(
        [
            'success' => false,
            'error' => [
                'code' => 'WORK_NOT_FOUND',
                'message' => $error->getMessage(),
            ],
        ],
        404
    );
}

/*
|--------------------------------------------------------------------------
| Release the PHP session lock
|--------------------------------------------------------------------------
|
| AI generation may take several seconds. The session must be closed
| before streaming so another browser request is not blocked behind it.
|
*/

session_write_close();

/*
|--------------------------------------------------------------------------
| Prepare newline-delimited JSON stream
|--------------------------------------------------------------------------
*/

ignore_user_abort(true);
set_time_limit(300);

ini_set('zlib.output_compression', '0');
ini_set('output_buffering', '0');

while (ob_get_level() > 0) {
    @ob_end_flush();
}

http_response_code(200);

header(
    'Content-Type: application/x-ndjson; charset=utf-8'
);

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Accel-Buffering: no');
header('X-Content-Type-Options: nosniff');

emitStreamEvent([
    'type' => 'response.started',

    'workId' => $work->id(),

    'pillar' => $pillar,

    'author' => 'Creative Partner',
]);

/*
|--------------------------------------------------------------------------
| Generate, relay, and persist the Creative Partner response
|--------------------------------------------------------------------------
*/

try {
    $savedMessage = $container
        ->creativePartner()
        ->respond(
            user: $authenticatedUser,
            workId: $work->id(),
            pillarValue: $pillar,

            onTextDelta: static function (
                string $delta,
            ): void {
                emitStreamEvent([
                    'type' => 'response.delta',
                    'delta' => $delta,
                ]);
            },
        );

    emitStreamEvent([
        'type' => 'response.completed',

        'message' => [
            'id' => $savedMessage->id(),

            'workId' => $savedMessage->workId(),

            'pillar' => (
                $savedMessage
                    ->pillar()
                    ->value
            ),

            'role' => (
                $savedMessage
                    ->role()
                    ->value
            ),

            'author' => 'Creative Partner',

            'content' => $savedMessage->content(),

            'createdAt' => (
                $savedMessage
                    ->createdAt()
                    ->format(DATE_ATOM)
            ),

            'displayTime' => (
                $savedMessage
                    ->createdAt()
                    ->format('M j, g:i A')
            ),
        ],
    ]);
} catch (\Throwable $error) {
    error_log(
        'Creative Partner response failed: '
        . $error->getMessage()
    );

    emitStreamEvent([
        'type' => 'response.error',

        'error' => [
            'code' => 'CREATIVE_PARTNER_FAILED',

            'message' => (
                'The Creative Partner could not respond. '
                . 'Your message remains safely saved.'
            ),
        ],
    ]);

    

} catch (\Throwable $error) {
    $diagnostic = sprintf(
        "[%s] %s in %s:%d\nStack trace:\n%s\n\n",
        date('Y-m-d H:i:s'),
        $error->getMessage(),
        $error->getFile(),
        $error->getLine(),
        $error->getTraceAsString(),
    );

    $logFile = dirname(__DIR__, 2)
    . '/storage/logs/forge.log';

$logDirectory = dirname($logFile);

if (!is_dir($logDirectory)) {
    mkdir(
        $logDirectory,
        0775,
        true
    );
}

error_log(
    sprintf(
        "[%s] %s in %s:%d\n%s\n\n",
        date('Y-m-d H:i:s'),
        $error->getMessage(),
        $error->getFile(),
        $error->getLine(),
        $error->getTraceAsString(),
    ),
    3,
    $logFile
);

    $isLocalEnvironment = (
        \env('APP_ENV', 'production') === 'local'
    );

    emitStreamEvent([
        'type' => 'response.error',

        'error' => [
            'code' => 'CREATIVE_PARTNER_FAILED',

            'message' => (
                'The Creative Partner could not respond. '
                . 'Your message remains safely saved.'
            ),

            /*
             * Included only during local development.
             */
            'detail' => $isLocalEnvironment
                ? $error->getMessage()
                : null,
        ],
    ]);
}