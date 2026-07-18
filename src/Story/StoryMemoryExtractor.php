<?php
declare(strict_types=1);

namespace SonicFoundry\Story;

use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Conversation\ConversationMessage;
use SonicFoundry\Conversation\ConversationRepository;
use SonicFoundry\Memory\MemoryExtraction;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class StoryMemoryExtractor
{
    private const MAX_CONTEXT_MESSAGES = 40;

    public function __construct(
        private readonly OpenAIClient $openAI,
        private readonly PromptAssembler $prompts,
        private readonly ConversationRepository $messages,
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
                pillar: WorkPillar::Story,
            );

        if (!$this->containsCreatorMessage($messages)) {
            throw new \DomainException(
                'Story memory cannot be extracted before '
                . 'the creator has contributed to the conversation.'
            );
        }

        $instructions = $this->prompts->assemble(
            promptPaths: [
                'memory/story-extractor.md',
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
                        'content' =>
                            $this->buildTranscript($messages),
                    ],
                ],

                schemaName:
                    'story_memory_extraction',

                schema:
                    $this->extractionSchema(),
            );

        return new MemoryExtraction(
            summary: $this->nullableString(
                $result['summary'] ?? null
            ),

            perspective: $this->nullableString(
                $result['perspective'] ?? null
            ),

            coreTension: $this->nullableString(
                $result['core_tension'] ?? null
            ),

            listenerTakeaway: $this->nullableString(
                $result['listener_takeaway'] ?? null
            ),

            themes: $this->stringList(
                $result['themes'] ?? []
            ),

            keySubjects: $this->stringList(
                $result['key_subjects'] ?? []
            ),

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

    /**
     * @param list<ConversationMessage> $messages
     */
    private function buildTranscript(
        array $messages,
    ): string {
        $messages = array_slice(
            $messages,
            -self::MAX_CONTEXT_MESSAGES
        );

        $lines = [
            '# Story Conversation Transcript',
            '',
        ];

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

            $lines[] = '## ' . $speaker;
            $lines[] = '';
            $lines[] = $message->content();
            $lines[] = '';
        }

        return trim(
            implode("\n", $lines)
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
                'summary' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'perspective' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'core_tension' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'listener_takeaway' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],

                'themes' => [
                    'type' => 'array',

                    'items' => [
                        'type' => 'string',
                    ],

                    'maxItems' => 8,
                ],

                'key_subjects' => [
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
                'summary',
                'perspective',
                'core_tension',
                'listener_takeaway',
                'themes',
                'key_subjects',
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

        return array_values(
            array_filter(
                $value,
                static fn (mixed $item): bool =>
                    is_string($item)
                    && trim($item) !== ''
            )
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