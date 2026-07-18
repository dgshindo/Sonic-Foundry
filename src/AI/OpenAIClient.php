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
     * Stream a normal text response.
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
            'store' => false,
        ];

        $encodedPayload = $this->encodePayload(
            $payload
        );

        $curl = $this->initializeCurl(
            accept: 'text/event-stream'
        );

        $sseBuffer = '';
        $completeText = '';
        $streamError = null;

        curl_setopt_array(
            $curl,
            [
                CURLOPT_POSTFIELDS => $encodedPayload,
                CURLOPT_RETURNTRANSFER => false,

                CURLOPT_WRITEFUNCTION => function (
                    mixed $handle,
                    string $chunk,
                ) use (
                    &$sseBuffer,
                    &$completeText,
                    &$streamError,
                    $onTextDelta,
                ): int {
                    $sseBuffer .= str_replace(
                        "\r\n",
                        "\n",
                        $chunk
                    );

                    while (
                        ($boundary = strpos(
                            $sseBuffer,
                            "\n\n"
                        )) !== false
                    ) {
                        $eventBlock = substr(
                            $sseBuffer,
                            0,
                            $boundary
                        );

                        $sseBuffer = substr(
                            $sseBuffer,
                            $boundary + 2
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
     * Request a strict structured response.
     *
     * @param list<array{
     *     role: string,
     *     content: string
     * }> $input
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    public function structuredResponse(
        string $instructions,
        array $input,
        string $schemaName,
        array $schema,
    ): array {
        if (
            !preg_match(
                '/^[A-Za-z0-9_-]+$/',
                $schemaName
            )
        ) {
            throw new \InvalidArgumentException(
                'Structured-output schema name is invalid.'
            );
        }

        $payload = [
            'model' => $this->model,
            'instructions' => $instructions,
            'input' => $input,
            'store' => false,

            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $schemaName,
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        $curl = $this->initializeCurl(
            accept: 'application/json'
        );

        curl_setopt_array(
            $curl,
            [
                CURLOPT_POSTFIELDS =>
                    $this->encodePayload($payload),

                CURLOPT_RETURNTRANSFER => true,
            ]
        );

        $rawResponse = curl_exec($curl);

        $curlError = curl_error($curl);

        $statusCode = (int) curl_getinfo(
            $curl,
            CURLINFO_RESPONSE_CODE
        );

        curl_close($curl);

        if ($rawResponse === false) {
            throw new \RuntimeException(
                $curlError !== ''
                    ? 'OpenAI request failed: ' . $curlError
                    : 'The OpenAI request failed.'
            );
        }

        try {
            $response = json_decode(
                $rawResponse,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $error) {
            throw new \RuntimeException(
                'OpenAI returned unreadable JSON.',
                previous: $error
            );
        }

        if (!is_array($response)) {
            throw new \RuntimeException(
                'OpenAI returned an invalid response.'
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $apiMessage =
                $response['error']['message']
                ?? null;

            throw new \RuntimeException(
                is_string($apiMessage)
                    ? 'OpenAI request failed: ' . $apiMessage
                    : (
                        'OpenAI returned HTTP status '
                        . $statusCode
                        . '.'
                    )
            );
        }

        $outputText = $this->extractOutputText(
            $response
        );

        try {
            $structuredData = json_decode(
                $outputText,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $error) {
            throw new \RuntimeException(
                'OpenAI returned invalid structured output.',
                previous: $error
            );
        }

        if (!is_array($structuredData)) {
            throw new \RuntimeException(
                'OpenAI structured output was not an object.'
            );
        }

        return $structuredData;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodePayload(
        array $payload,
    ): string {
        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @return \CurlHandle
     */
    private function initializeCurl(
        string $accept,
    ): \CurlHandle {
        $curl = curl_init(
            self::RESPONSES_ENDPOINT
        );

        if ($curl === false) {
            throw new \RuntimeException(
                'Unable to initialize the OpenAI request.'
            );
        }

        curl_setopt_array(
            $curl,
            [
                CURLOPT_POST => true,

                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer '
                        . $this->apiKey,

                    'Content-Type: application/json',

                    'Accept: ' . $accept,
                ],

                CURLOPT_HEADER => false,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => 240,
            ]
        );

        return $curl;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractOutputText(
        array $response,
    ): string {
        $segments = [];

        foreach (
            $response['output'] ?? []
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
                    === 'refusal'
                ) {
                    $refusal =
                        $contentItem['refusal']
                        ?? 'The model refused the request.';

                    throw new \RuntimeException(
                        is_string($refusal)
                            ? $refusal
                            : 'The model refused the request.'
                    );
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
                    $segments[] = $text;
                }
            }
        }

        $outputText = trim(
            implode('', $segments)
        );

        if ($outputText === '') {
            throw new \RuntimeException(
                'OpenAI returned no output text.'
            );
        }

        return $outputText;
    }

    /**
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
                $event['response']
                    ['incomplete_details']
                    ['reason']
                ?? null;

            $streamError = is_string($reason)
                ? (
                    'The OpenAI response was incomplete: '
                    . $reason
                )
                : 'The OpenAI response was incomplete.';
        }
    }
}