<?php
declare(strict_types=1);

namespace SonicFoundry\AI;

final class OpenAIClient
{
    private const RESPONSES_ENDPOINT =
        'https://api.openai.com/v1/responses';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {
        if (trim($this->apiKey) === '') {
            throw new \RuntimeException(
                'OPENAI_API_KEY is not configured.'
            );
        }

        if (trim($this->model) === '') {
            throw new \RuntimeException(
                'OPENAI_MODEL is not configured.'
            );
        }

        if (!extension_loaded('curl')) {
            throw new \RuntimeException(
                'The PHP cURL extension is required.'
            );
        }
    }

    /**
     * Stream a response from OpenAI.
     *
     * @param list<array{
     *     role: string,
     *     content: string
     * }> $input
     *
     * @param callable(string): void $onTextDelta
     */
    public function streamResponse(
        string $instructions,
        array $input,
        callable $onTextDelta,
    ): string {
        $payload = [
            'model' => $this->model,
            'instructions' => $instructions,
            'input' => $input,
            'stream' => true,

            /*
             * Sonic Foundry persists its own conversation history.
             * We do not require OpenAI-side response storage here.
             */
            'store' => false,
        ];

        $encodedPayload = json_encode(
            $payload,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );

        $curl = curl_init(
            self::RESPONSES_ENDPOINT
        );

        if ($curl === false) {
            throw new \RuntimeException(
                'Unable to initialize the OpenAI request.'
            );
        }

        $sseBuffer = '';
        $completeText = '';
        $streamError = null;

        curl_setopt_array(
            $curl,
            [
                CURLOPT_POST => true,

                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer '
                        . $this->apiKey,

                    'Content-Type: application/json',

                    'Accept: text/event-stream',
                ],

                CURLOPT_POSTFIELDS => $encodedPayload,

                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => false,

                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => 240,

                CURLOPT_WRITEFUNCTION => function (
                    mixed $handle,
                    string $chunk,
                ) use (
                    &$sseBuffer,
                    &$completeText,
                    &$streamError,
                    $onTextDelta,
                ): int {
                    /*
                     * Normalize CRLF so SSE event boundaries are
                     * consistently represented as two newlines.
                     */
                    $sseBuffer .= str_replace(
                        "\r\n",
                        "\n",
                        $chunk
                    );

                    while (
                        ($eventBoundary = strpos(
                            $sseBuffer,
                            "\n\n"
                        )) !== false
                    ) {
                        $eventBlock = substr(
                            $sseBuffer,
                            0,
                            $eventBoundary
                        );

                        $sseBuffer = substr(
                            $sseBuffer,
                            $eventBoundary + 2
                        );

                        $this->processEventBlock(
                            eventBlock: $eventBlock,
                            completeText: $completeText,
                            streamError: $streamError,
                            onTextDelta: $onTextDelta,
                        );
                    }

                    return strlen($chunk);
                },
            ]
        );

        $executed = curl_exec($curl);

        $curlError = curl_error($curl);

        $statusCode = (int) curl_getinfo(
            $curl,
            CURLINFO_RESPONSE_CODE
        );

        curl_close($curl);

        /*
         * Process a final event if the connection ended without an
         * additional blank line.
         */
        if (trim($sseBuffer) !== '') {
            $this->processEventBlock(
                eventBlock: trim($sseBuffer),
                completeText: $completeText,
                streamError: $streamError,
                onTextDelta: $onTextDelta,
            );
        }

        if ($executed === false) {
            throw new \RuntimeException(
                $curlError !== ''
                    ? 'OpenAI request failed: ' . $curlError
                    : 'The OpenAI request failed.'
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(
                'OpenAI returned HTTP status '
                . $statusCode
                . '.'
            );
        }

        if ($streamError !== null) {
            throw new \RuntimeException(
                $streamError
            );
        }

        $completeText = trim($completeText);

        if ($completeText === '') {
            throw new \RuntimeException(
                'The Creative Partner returned no text.'
            );
        }

        return $completeText;
    }

    /**
     * Process one OpenAI server-sent event.
     *
     * @param callable(string): void $onTextDelta
     */
    private function processEventBlock(
        string $eventBlock,
        string &$completeText,
        ?string &$streamError,
        callable $onTextDelta,
    ): void {
        if (trim($eventBlock) === '') {
            return;
        }

        $eventName = '';
        $dataLines = [];

        $lines = preg_split(
            '/\n/',
            $eventBlock
        );

        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            if (str_starts_with($line, ':')) {
                /*
                 * SSE comment or keep-alive.
                 */
                continue;
            }

            if (str_starts_with($line, 'event:')) {
                $eventName = trim(
                    substr($line, 6)
                );

                continue;
            }

            if (str_starts_with($line, 'data:')) {
                $dataLines[] = ltrim(
                    substr($line, 5)
                );
            }
        }

        if ($dataLines === []) {
            return;
        }

        $rawData = implode(
            "\n",
            $dataLines
        );

        if ($rawData === '[DONE]') {
            return;
        }

        try {
            $event = json_decode(
                $rawData,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            /*
             * Ignore malformed or non-JSON SSE events rather than
             * corrupting the completed response.
             */
            return;
        }

        if (!is_array($event)) {
            return;
        }

        $eventType = is_string(
            $event['type'] ?? null
        )
            ? $event['type']
            : $eventName;

        if (
            $eventType === 'response.output_text.delta'
            && is_string($event['delta'] ?? null)
        ) {
            $delta = $event['delta'];

            $completeText .= $delta;

            $onTextDelta($delta);

            return;
        }

        if ($eventType === 'error') {
            $message =
                $event['error']['message']
                ?? $event['message']
                ?? 'The OpenAI stream failed.';

            $streamError = is_string($message)
                ? $message
                : 'The OpenAI stream failed.';

            return;
        }

        if ($eventType === 'response.failed') {
            $message =
                $event['response']['error']['message']
                ?? 'The OpenAI response failed.';

            $streamError = is_string($message)
                ? $message
                : 'The OpenAI response failed.';

            return;
        }

        if ($eventType === 'response.incomplete') {
            $reason =
                $event['response']['incomplete_details']['reason']
                ?? null;

            $streamError = is_string($reason)
                ? 'The OpenAI response was incomplete: '
                    . $reason
                : 'The OpenAI response was incomplete.';
        }
    }
}