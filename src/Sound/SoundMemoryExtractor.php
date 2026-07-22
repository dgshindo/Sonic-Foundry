<?php
declare(strict_types=1);

namespace SonicFoundry\Sound;

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

final class SoundMemoryExtractor
{
    private const MAX_CONTEXT_MESSAGES = 40;

    public function __construct(
        private OpenAIClient $openAI,
        private PromptAssembler $prompts,
        private ConversationRepository $messages,
        private PillarMemoryService $memory,
        private WorkService $works,
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
                pillar: WorkPillar::Sound,
            );

        if (!$this->containsCreatorMessage($messages)) {
            throw new \DomainException(
                'Sound memory cannot be extracted before '
                . 'the creator has contributed to the conversation.'
            );
        }

        $instructions = $this->prompts->assemble(
            promptPaths: [
                'memory/sound-extractor.md',
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

                            storyMemory:
                                $this->confirmedMemory(
                                    user: $user,
                                    workId: $work->id(),
                                    pillar: WorkPillar::Story,
                                ),

                            emotionMemory:
                                $this->confirmedMemory(
                                    user: $user,
                                    workId: $work->id(),
                                    pillar: WorkPillar::Emotion,
                                ),

                            identityMemory:
                                $this->confirmedMemory(
                                    user: $user,
                                    workId: $work->id(),
                                    pillar: WorkPillar::Identity,
                                ),
                        ),
                    ],
                ],

                schemaName:
                    'sound_memory_extraction',

                schema:
                    $this->extractionSchema(),
            );

        return new MemoryExtraction(
            data: [
                'schema_version' => 1,

                'sonic_identity' =>
                    $this->nullableString(
                        $result['sonic_identity']
                            ?? null
                    ),

                'energy_profile' =>
                    $this->nullableString(
                        $result['energy_profile']
                            ?? null
                    ),

                'instrumentation_direction' =>
                    $this->stringList(
                        $result['instrumentation_direction']
                            ?? []
                    ),

                'vocal_character' =>
                    $this->nullableString(
                        $result['vocal_character']
                            ?? null
                    ),

                'production_aesthetic' =>
                    $this->nullableString(
                        $result['production_aesthetic']
                            ?? null
                    ),

                'rhythmic_feel' =>
                    $this->nullableString(
                        $result['rhythmic_feel']
                            ?? null
                    ),

                'harmonic_language' =>
                    $this->nullableString(
                        $result['harmonic_language']
                            ?? null
                    ),

                'listening_environment' =>
                    $this->nullableString(
                        $result['listening_environment']
                            ?? null
                    ),
            ],

            confidence:
                $this->nullableFloat(
                    $result['confidence']
                        ?? null
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

    private function confirmedMemory(
        AuthenticatedUser $user,
        int $workId,
        WorkPillar $pillar,
    ): ?PillarMemory {
        $memory = $this->memory
            ->memoryForWork(
                user: $user,
                workId: $workId,
                pillarValue: $pillar->value,
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
        ?PillarMemory $emotionMemory,
        ?PillarMemory $identityMemory,
    ): string {
        $sections = [];

        if ($storyMemory !== null) {
            $sections[] =
                '# Confirmed Story Context';

            $sections[] = '';

            $sections[] =
                $this->encodeDocument(
                    $storyMemory->data()
                );

            $sections[] = '';
        }

        if ($emotionMemory !== null) {
            $sections[] =
                '# Confirmed Emotion Context';

            $sections[] = '';

            $sections[] =
                $this->encodeDocument(
                    $emotionMemory->data()
                );

            $sections[] = '';
        }

        if ($identityMemory !== null) {
            $sections[] =
                '# Confirmed Identity Context';

            $sections[] = '';

            $sections[] =
                $this->encodeDocument(
                    $identityMemory->data()
                );

            $sections[] = '';
        }

        $sections[] =
            '# Sound Conversation Transcript';

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

            $speaker =
                $message->isUserMessage()
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
     * @param array<string, mixed> $document
     */
    private function encodeDocument(
        array $document,
    ): string {
        return json_encode(
            $document,
            JSON_THROW_ON_ERROR
            | JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
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
                'sonic_identity' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'energy_profile' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'instrumentation_direction' => [
                    'type' => 'array',

                    'items' => [
                        'type' => 'string',
                    ],

                    'maxItems' => 8,
                ],

                'vocal_character' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'production_aesthetic' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'rhythmic_feel' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'harmonic_language' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'listening_environment' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
            ],

            'required' => [
                'sonic_identity',
                'energy_profile',
                'instrumentation_direction',
                'vocal_character',
                'production_aesthetic',
                'rhythmic_feel',
                'harmonic_language',
                'listening_environment',
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

            $comparisonKey =
                mb_strtolower($item);

            if (
                isset(
                    $normalized[
                        $comparisonKey
                    ]
                )
            ) {
                continue;
            }

            $normalized[
                $comparisonKey
            ] = $item;
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

        $confidence =
            (float) $value;

        if (
            $confidence < 0.0
            || $confidence > 1.0
        ) {
            return null;
        }

        return $confidence;
    }
}