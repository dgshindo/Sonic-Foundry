<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2)
    . '/config/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Local-development protection
|--------------------------------------------------------------------------
*/

$appEnvironment = strtolower(
    (string) env(
        'APP_ENV',
        'production'
    )
);

if ($appEnvironment !== 'local') {
    http_response_code(404);
    exit('Not Found');
}

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

$apiKey = trim(
    (string) env(
        'OPENAI_API_KEY',
        ''
    )
);

$model = trim(
    (string) env(
        'OPENAI_MODEL',
        ''
    )
);

$errorMessage = null;
$joke = null;
$responseId = null;
$responseModel = null;
$elapsedMilliseconds = null;
$httpStatus = null;

/*
|--------------------------------------------------------------------------
| Configuration validation
|--------------------------------------------------------------------------
*/

if ($apiKey === '') {
    $errorMessage =
        'OPENAI_API_KEY is not configured.';
} elseif ($model === '') {
    $errorMessage =
        'OPENAI_MODEL is not configured.';
} elseif (!extension_loaded('curl')) {
    $errorMessage =
        'The PHP cURL extension is not enabled.';
}

/*
|--------------------------------------------------------------------------
| OpenAI request
|--------------------------------------------------------------------------
*/

if ($errorMessage === null) {
    $payload = [
        'model' => $model,

        'instructions' => (
            'You are testing the OpenAI connection for '
            . 'Sonic Foundry. Return one original, harmless joke. '
            . 'Keep it concise. Return only the joke.'
        ),

        'input' => (
            'Tell me a random joke.'
        ),

        /*
         * This diagnostic does not need OpenAI-side storage.
         */
        'store' => false,
    ];

    try {
        $encodedPayload = json_encode(
            $payload,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );

        $curl = curl_init(
            'https://api.openai.com/v1/responses'
        );

        if ($curl === false) {
            throw new RuntimeException(
                'Unable to initialize cURL.'
            );
        }

        curl_setopt_array(
            $curl,
            [
                CURLOPT_POST => true,

                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer '
                        . $apiKey,

                    'Content-Type: application/json',

                    'Accept: application/json',
                ],

                CURLOPT_POSTFIELDS => $encodedPayload,

                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,

                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => 120,
            ]
        );

        $startedAt = hrtime(true);

        $rawResponse = curl_exec($curl);

        $elapsedMilliseconds = round(
            (
                hrtime(true)
                - $startedAt
            ) / 1_000_000,
            2
        );

        $curlError = curl_error($curl);

        $httpStatus = (int) curl_getinfo(
            $curl,
            CURLINFO_RESPONSE_CODE
        );

        curl_close($curl);

        if ($rawResponse === false) {
            throw new RuntimeException(
                $curlError !== ''
                    ? 'cURL request failed: '
                        . $curlError
                    : 'The OpenAI request failed.'
            );
        }

        $responseData = json_decode(
            $rawResponse,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($responseData)) {
            throw new RuntimeException(
                'OpenAI returned an invalid response.'
            );
        }

        if (
            $httpStatus < 200
            || $httpStatus >= 300
        ) {
            $apiError = $responseData['error']['message']
                ?? 'OpenAI returned an unsuccessful response.';

            throw new RuntimeException(
                sprintf(
                    'OpenAI HTTP %d: %s',
                    $httpStatus,
                    is_string($apiError)
                        ? $apiError
                        : 'Unknown API error.'
                )
            );
        }

        $responseId = is_string(
            $responseData['id'] ?? null
        )
            ? $responseData['id']
            : null;

        $responseModel = is_string(
            $responseData['model'] ?? null
        )
            ? $responseData['model']
            : $model;

        /*
         * Extract output text from the Responses API output array.
         */
        $textSegments = [];

        foreach (
            $responseData['output'] ?? []
            as $outputItem
        ) {
            if (!is_array($outputItem)) {
                continue;
            }

            foreach (
                $outputItem['content'] ?? []
                as $contentItem
            ) {
                if (!is_array($contentItem)) {
                    continue;
                }

                if (
                    ($contentItem['type'] ?? null)
                    !== 'output_text'
                ) {
                    continue;
                }

                $text = $contentItem['text']
                    ?? null;

                if (
                    is_string($text)
                    && trim($text) !== ''
                ) {
                    $textSegments[] = $text;
                }
            }
        }

        $joke = trim(
            implode(
                '',
                $textSegments
            )
        );

        if ($joke === '') {
            throw new RuntimeException(
                'The request succeeded, but no output text was returned.'
            );
        }
    } catch (Throwable $error) {
        $errorMessage = $error->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        OpenAI Connection Test | Sonic Foundry
    </title>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >

    <style>
        .openai-test-page {
            display: grid;
            min-height: 100vh;
            place-items: center;

            padding: var(--space-8);

            background:
                radial-gradient(
                    circle at 50% 0%,
                    rgba(255, 106, 0, 0.12),
                    transparent 30rem
                ),
                var(--color-background);
        }

        .openai-test-card {
            width: min(100%, 760px);
            padding:
                clamp(
                    var(--space-8),
                    6vw,
                    var(--space-12)
                );

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.035),
                    rgba(255, 255, 255, 0.008)
                ),
                var(--color-panel);

            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);

            box-shadow: var(--shadow-heavy);
        }

        .openai-test-card__logo {
            display: block;

            width: var(--logo-md);
            height: var(--logo-md);

            margin:
                0
                auto
                var(--space-6);

            object-fit: contain;
        }

        .openai-test-card h1 {
            margin-bottom: var(--space-4);
            text-align: center;
        }

        .openai-test-card__introduction {
            margin-bottom: var(--space-8);

            color: var(--color-text-muted);
            text-align: center;
        }

        .openai-test-result {
            padding: var(--space-6);

            background: rgba(255, 255, 255, 0.018);
            border: 1px solid var(--color-border-muted);
            border-radius: var(--radius-md);
        }

        .openai-test-result--success {
            border-left:
                3px solid
                var(--color-ember);
        }

        .openai-test-result--error {
            border-left:
                3px solid
                var(--color-error);
        }

        .openai-test-result h2 {
            margin-bottom: var(--space-4);
        }

        .openai-test-result p:last-child {
            margin-bottom: 0;
        }

        .openai-test-metadata {
            display: grid;
            gap: var(--space-3);

            margin:
                var(--space-8)
                0
                0;
        }

        .openai-test-metadata div {
            display: grid;
            grid-template-columns:
                minmax(7rem, auto)
                minmax(0, 1fr);
            gap: var(--space-4);

            padding-bottom: var(--space-3);

            border-bottom:
                1px solid
                var(--color-border-muted);
        }

        .openai-test-metadata dt {
            color: var(--color-text-muted);
            font-size: var(--font-size-xs);
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .openai-test-metadata dd {
            margin: 0;

            overflow-wrap: anywhere;

            color: var(--color-text-soft);
        }

        .openai-test-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: var(--space-4);

            margin-top: var(--space-8);
        }
    </style>
</head>

<body>
    <main class="openai-test-page">
        <section class="openai-test-card">
            <img
                class="openai-test-card__logo"
                src="/assets/images/sonic-foundry-logo.png"
                alt=""
            >

            <p class="eyebrow">
                Development Diagnostic
            </p>

            <h1>
                OpenAI Connection Test
            </h1>

            <p class="openai-test-card__introduction">
                This page bypasses the Forge prompt and
                conversation layers.
            </p>

            <?php if ($errorMessage !== null): ?>
                <section
                    class="
                        openai-test-result
                        openai-test-result--error
                    "
                    role="alert"
                >
                    <h2>
                        Test Failed
                    </h2>

                    <p>
                        <?= htmlspecialchars(
                            $errorMessage,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                </section>
            <?php else: ?>
                <section
                    class="
                        openai-test-result
                        openai-test-result--success
                    "
                >
                    <h2>
                        OpenAI Responded
                    </h2>

                    <p>
                        <?= nl2br(
                            htmlspecialchars(
                                (string) $joke,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ) ?>
                    </p>
                </section>
            <?php endif; ?>

            <dl class="openai-test-metadata">
                <div>
                    <dt>
                        Configured model
                    </dt>

                    <dd>
                        <?= htmlspecialchars(
                            $model !== ''
                                ? $model
                                : 'Not configured',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>
                        Response model
                    </dt>

                    <dd>
                        <?= htmlspecialchars(
                            $responseModel
                                ?? 'Not returned',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>
                        HTTP status
                    </dt>

                    <dd>
                        <?= htmlspecialchars(
                            $httpStatus !== null
                                ? (string) $httpStatus
                                : 'No response',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>
                        Response ID
                    </dt>

                    <dd>
                        <?= htmlspecialchars(
                            $responseId
                                ?? 'Not returned',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>

                <div>
                    <dt>
                        Request time
                    </dt>

                    <dd>
                        <?= htmlspecialchars(
                            $elapsedMilliseconds !== null
                                ? $elapsedMilliseconds . ' ms'
                                : 'Not completed',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </dd>
                </div>
            </dl>

            <div class="openai-test-actions">
                <a
                    class="button button--primary"
                    href="/dev/openai-test.php"
                >
                    Tell Another Joke
                </a>

                <a
                    class="button button--secondary"
                    href="/workspace.php"
                >
                    Return to Workspace
                </a>
            </div>
        </section>
    </main>
</body>
</html>