<?php
declare(strict_types=1);

namespace SonicFoundry\Emotion;

use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Conversation\ConversationMessage;
use SonicFoundry\Conversation\ConversationRepository;
use SonicFoundry\Memory\MemoryExtraction;
use SonicFoundry\Memory\PillarMemory;
use SonicFoundry\Memory\PillarMemoryService;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class EmotionMemoryExtractor
{
    private const MAX_CONTEXT_MESSAGES = 40;

    public function __construct(
        private readonly OpenAIClient $openAI,
        private readonly PromptAssembler $prompts,
        private readonly ConversationRepository $messages,
        private readonly PillarMemoryService $memory,
        private readonly WorkService $works,
    ) {
    }

    public function extract(
        AuthenticatedUser $user,
        int $workId,
    ): MemoryExtraction {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $messages = $this->messages
            ->findForWorkAndPillar(
                workId: $work->id(),
                pillar: WorkPillar::Emotion,
            );

        if (!$this->containsCreatorMessage($messages)) {
            throw new \DomainException(
                'Emotion memory cannot be extracted before '
                . 'the creator has contributed to the conversation.'
            );
        }

        $storyMemory = $this->confirmedStoryMemory(
            user: $user,
            workId: $work->id(),
        );

        $instructions = $this->prompts->assemble(
            promptPaths: [
                'memory/emotion-extractor.md',
            ],

            variables: [
                'work_title' => $work->title(),
                'work_type' => $work->typeLabel(),
            ],
        );

        $result = $this->openAI
            ->structuredResponse(
                instructions: $instructions,

                input: [
                    [
                        'role' => 'user',

                        'content' => $this->buildInputDocument(
                            messages: $messages,
                            storyMemory: $storyMemory,
                        ),
                    ],
                ],

                schemaName:
                    'emotion_memory_extraction',

                schema:
                    $this->extractionSchema(),
            );

        return new MemoryExtraction(
            data: [
                'schema_version' => 1,

                'emotional_core' =>
                    $this->nullableString(
                        $result['emotional_core']
                            ?? null
                    ),

                'starting_emotion' =>
                    $this->nullableString(
                        $result['starting_emotion']
                            ?? null
                    ),

                'ending_emotion' =>
                    $this->nullableString(
                        $result['ending_emotion']
                            ?? null
                    ),

                'emotional_arc' =>
                    $this->nullableString(
                        $result['emotional_arc']
                            ?? null
                    ),

                'emotional_stakes' =>
                    $this->nullableString(
                        $result['emotional_stakes']
                            ?? null
                    ),

                'desired_listener_feeling' =>
                    $this->nullableString(
                        $result['desired_listener_feeling']
                            ?? null
                    ),

                'emotional_contrasts' =>
                    $this->stringList(
                        $result['emotional_contrasts']
                            ?? []
                    ),

                'emotional_touchstones' =>
                    $this->stringList(
                        $result['emotional_touchstones']
                            ?? []
                    ),
            ],

            confidence: $this->nullableFloat(
                $result['confidence'] ?? null
            ),
        );
    }

    /**
     * @param list<ConversationMessage> $messages
     */
    private function containsCreatorMessage(
        array $messages,
    ): bool {
        foreach ($messages as $message) {
            if ($message->isUserMessage()) {
                return true;
            }
        }

        return false;
    }

    private function confirmedStoryMemory(
        AuthenticatedUser $user,
        int $workId,
    ): ?PillarMemory {
        $memory = $this->memory->memoryForWork(
            user: $user,
            workId: $workId,
            pillarValue: WorkPillar::Story->value,
        );

        if (
            $memory === null
            || !$memory->isConfirmed()
        ) {
            return null;
        }

        return $memory;
    }

    /**
     * @param list<ConversationMessage> $messages
     */
    private function buildInputDocument(
        array $messages,
        ?PillarMemory $storyMemory,
    ): string {
        $sections = [];

        if ($storyMemory !== null) {
            $sections[] =
                '# Confirmed Story Context';

            $sections[] = '';

            $sections[] = json_encode(
                $storyMemory->data(),
                JSON_THROW_ON_ERROR
                | JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
            );

            $sections[] = '';
        }

        $sections[] =
            '# Emotion Conversation Transcript';

        $sections[] = '';

        $messages = array_slice(
            $messages,
            -self::MAX_CONTEXT_MESSAGES
        );

        foreach ($messages as $message) {
            if (
                !$message->isUserMessage()
                && !$message->isPartnerMessage()
            ) {
                continue;
            }

            $speaker = $message->isUserMessage()
                ? 'Creator'
                : 'Creative Partner';

            $sections[] =
                '## ' . $speaker;

            $sections[] = '';

            $sections[] =
                $message->content();

            $sections[] = '';
        }

        return trim(
            implode("\n", $sections)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function extractionSchema(): array
    {
        return [
            'type' => 'object',

            'properties' => [
                'emotional_core' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'starting_emotion' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'ending_emotion' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'emotional_arc' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'emotional_stakes' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'desired_listener_feeling' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'emotional_contrasts' => [
                    'type' => 'array',

                    'items' => [
                        'type' => 'string',
                    ],

                    'maxItems' => 8,
                ],

                'emotional_touchstones' => [
                    'type' => 'array',

                    'items' => [
                        'type' => 'string',
                    ],

                    'maxItems' => 8,
                ],

                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
            ],

            'required' => [
                'emotional_core',
                'starting_emotion',
                'ending_emotion',
                'emotional_arc',
                'emotional_stakes',
                'desired_listener_feeling',
                'emotional_contrasts',
                'emotional_touchstones',
                'confidence',
            ],

            'additionalProperties' => false,
        ];
    }

    private function nullableString(
        mixed $value,
    ): ?string {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }

    /**
     * @return list<string>
     */
    private function stringList(
        mixed $value,
    ): array {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }

            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $key = mb_strtolower($item);

            if (isset($normalized[$key])) {
                continue;
            }

            $normalized[$key] = $item;
        }

        return array_values(
            $normalized
        );
    }

    private function nullableFloat(
        mixed $value,
    ): ?float {
        if (
            !is_int($value)
            && !is_float($value)
        ) {
            return null;
        }

        $confidence = (float) $value;

        if (
            $confidence < 0.0
            || $confidence > 1.0
        ) {
            return null;
        }

        return $confidence;
    }
}