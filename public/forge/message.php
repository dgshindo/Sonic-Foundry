<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/config/bootstrap.php';

//use DomainException;
use SonicFoundry\Auth\Session;

/**
 * Return a standardized JSON response and terminate execution.
 *
 * @param array<string, mixed> $payload
 */
function respondJson(
    array $payload,
    int $statusCode = 200
): never {
    http_response_code($statusCode);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('Vary: Accept');

    echo json_encode(
        $payload,
        JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

    exit;
}

/**
 * Determine whether the browser expects a JSON response.
 */
function requestExpectsJson(): bool
{
    $acceptHeader = strtolower(
        (string) ($_SERVER['HTTP_ACCEPT'] ?? '')
    );

    $requestedWith = strtolower(
        (string) (
            $_SERVER['HTTP_X_REQUESTED_WITH']
            ?? ''
        )
    );

    return str_contains(
        $acceptHeader,
        'application/json'
    ) || $requestedWith === 'xmlhttprequest';
}

$expectsJson = requestExpectsJson();

/*
|--------------------------------------------------------------------------
| Request method
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    if ($expectsJson) {
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

    http_response_code(405);

    exit('Method Not Allowed');
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| AJAX requests must receive JSON rather than an HTML redirect.
|
*/

$authenticatedUser = $auth->user();

if (!$authenticatedUser) {
    if ($expectsJson) {
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

    Session::flash(
        'auth_error',
        'Please sign in to continue.'
    );

    header('Location: /login.php');
    exit;
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

$messageContent = trim(
    (string) ($_POST['message'] ?? '')
);

$csrfToken = isset($_POST['csrf_token'])
    ? (string) $_POST['csrf_token']
    : null;

/*
|--------------------------------------------------------------------------
| Safe fallback destination
|--------------------------------------------------------------------------
*/

$redirectUrl = '/workspace.php';

if ($workId !== null) {
    $redirectPillar = $pillar !== ''
        ? $pillar
        : 'story';

    $redirectUrl = '/forge.php?work='
        . $workId
        . '&pillar='
        . rawurlencode($redirectPillar);
}

/*
|--------------------------------------------------------------------------
| CSRF verification
|--------------------------------------------------------------------------
*/

if (!Session::verifyCsrfToken($csrfToken)) {
    $message = (
        'Your form session expired. '
        . 'Refresh the Forge and try again.'
    );

    if ($expectsJson) {
        respondJson(
            [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'CSRF_FAILED',
                    'message' => $message,
                ],
            ],
            419
        );
    }

    Session::flash(
        'forge_error',
        $message
    );

    Session::flash(
        'forge_message',
        $messageContent
    );

    header('Location: ' . $redirectUrl);
    exit;
}

/*
|--------------------------------------------------------------------------
| Persist creator message
|--------------------------------------------------------------------------
*/

try {
    if ($workId === null) {
        throw new \DomainException(
            'A valid Work is required.'
        );
    }

    $savedMessage = $container
        ->conversationService()
        ->addUserMessage(
            user: $authenticatedUser,
            workId: $workId,
            pillarValue: $pillar,
            content: $messageContent,
        );

    if ($expectsJson) {
        respondJson([
            'success' => true,

            'data' => [
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

                    'author' => (
                        $authenticatedUser
                            ->firstName()
                    ),

                    'content' => (
                        $savedMessage
                            ->content()
                    ),

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
            ],

            'error' => null,
        ]);
    }

    header('Location: ' . $redirectUrl);
    exit;
} catch (\DomainException $error) {
    if ($expectsJson) {
        respondJson(
            [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => $error->getMessage(),
                ],
            ],
            422
        );
    }

    Session::flash(
        'forge_error',
        $error->getMessage()
    );

    Session::flash(
        'forge_message',
        $messageContent
    );

    header('Location: ' . $redirectUrl);
    exit;
} catch (Throwable $error) {
    error_log(
        'Forge message creation failed: '
        . $error->getMessage()
    );

    $publicMessage = (
        'Your message could not be saved. '
        . 'Please try again.'
    );

    if ($expectsJson) {
        respondJson(
            [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'MESSAGE_SAVE_FAILED',
                    'message' => $publicMessage,
                ],
            ],
            500
        );
    }

    Session::flash(
        'forge_error',
        $publicMessage
    );

    Session::flash(
        'forge_message',
        $messageContent
    );

    header('Location: ' . $redirectUrl);
    exit;
}