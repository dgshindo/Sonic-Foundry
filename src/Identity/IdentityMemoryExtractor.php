<?php
declare(strict_types=1);

namespace SonicFoundry\Identity;

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

final class IdentityMemoryExtractor
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
                pillar: WorkPillar::Identity,
            );

        if (!$this->containsCreatorMessage($messages)) {
            throw new \DomainException(
                'Identity memory cannot be extracted before '
                . 'the creator has contributed to the conversation.'
            );
        }

        $instructions = $this->prompts->assemble(
            promptPaths: [
                'memory/identity-extractor.md',
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
                        ),
                    ],
                ],

                schemaName:
                    'identity_memory_extraction',

                schema:
                    $this->extractionSchema(),
            );

        return new MemoryExtraction(
            data: [
                'schema_version' => 1,

                'core_identity' =>
                    $this->nullableString(
                        $result['core_identity']
                            ?? null
                    ),

                'creative_voice' =>
                    $this->nullableString(
                        $result['creative_voice']
                            ?? null
                    ),

                'audience_promise' =>
                    $this->nullableString(
                        $result['audience_promise']
                            ?? null
                    ),

                'authenticity_anchor' =>
                    $this->nullableString(
                        $result['authenticity_anchor']
                            ?? null
                    ),

                'distinctive_qualities' =>
                    $this->stringList(
                        $result['distinctive_qualities']
                            ?? []
                    ),

                'core_values' =>
                    $this->stringList(
                        $result['core_values']
                            ?? []
                    ),

                'identity_boundaries' =>
                    $this->stringList(
                        $result['identity_boundaries']
                            ?? []
                    ),

                'creator_relationship' =>
                    $this->nullableString(
                        $result['creator_relationship']
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
    ): string {
        $sections = [];

        if ($storyMemory !== null) {
            $sections[] = '# Confirmed Story Context';
            $sections[] = '';

            $sections[] = $this->encodeDocument(
                $storyMemory->data()
            );

            $sections[] = '';
        }

        if ($emotionMemory !== null) {
            $sections[] = '# Confirmed Emotion Context';
            $sections[] = '';

            $sections[] = $this->encodeDocument(
                $emotionMemory->data()
            );

            $sections[] = '';
        }

        $sections[] =
            '# Identity Conversation Transcript';

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

            $sections[] = '## ' . $speaker;
            $sections[] = '';
            $sections[] = $message->content();
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
                'core_identity' => [
                    'type' => ['string', 'null'],
                ],

                'creative_voice' => [
                    'type' => ['string', 'null'],
                ],

                'audience_promise' => [
                    'type' => ['string', 'null'],
                ],

                'authenticity_anchor' => [
                    'type' => ['string', 'null'],
                ],

                'distinctive_qualities' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'maxItems' => 8,
                ],

                'core_values' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'maxItems' => 8,
                ],

                'identity_boundaries' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'maxItems' => 8,
                ],

                'creator_relationship' => [
                    'type' => ['string', 'null'],
                ],

                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
            ],

            'required' => [
                'core_identity',
                'creative_voice',
                'audience_promise',
                'authenticity_anchor',
                'distinctive_qualities',
                'core_values',
                'identity_boundaries',
                'creator_relationship',
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

            if (isset($normalized[$comparisonKey])) {
                continue;
            }

            $normalized[$comparisonKey] = $item;
        }

        return array_values($normalized);
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