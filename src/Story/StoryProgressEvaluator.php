<?php
declare(strict_types=1);

namespace SonicFoundry\Story;

use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Memory\PillarMemory;
use SonicFoundry\Memory\PillarMemoryService;
use SonicFoundry\Progress\CriterionStatus;
use SonicFoundry\Progress\ProgressCriterion;
use SonicFoundry\Progress\ProgressEvaluation;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class StoryProgressEvaluator
{
    private const CRITERION_LABELS = [
        'central_meaning' => 'Central Meaning',
        'perspective' => 'Perspective',
        'core_tension' => 'Core Tension',
        'themes' => 'Themes',
        'key_subjects' => 'Key Subjects',
        'listener_takeaway' => 'Listener Takeaway',
    ];

    public function __construct(
        private readonly OpenAIClient $openAI,
        private readonly PromptAssembler $prompts,
        private readonly PillarMemoryService $memory,
        private readonly WorkService $works,
    ) {
    }

    public function evaluate(
        AuthenticatedUser $user,
        int $workId,
    ): ProgressEvaluation {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $memory = $this->memory->memoryForWork(
            user: $user,
            workId: $work->id(),
            pillarValue: WorkPillar::Story->value,
        );

        if (
            $memory === null
            || !$memory->isConfirmed()
        ) {
            throw new \DomainException(
                'Story progress requires confirmed Creative Memory.'
            );
        }

        $instructions = $this->prompts->assemble(
            promptPaths: [
                'progress/story-evaluator.md',
            ],

            variables: [
                'work_title' => $work->title(),
                'work_type' => $work->typeLabel(),
            ],
        );

        $result = $this->openAI->structuredResponse(
            instructions: $instructions,

            input: [
                [
                    'role' => 'user',
                    'content' => $this->serializeMemory(
                        $memory
                    ),
                ],
            ],

            schemaName: 'story_progress_evaluation',

            schema: $this->evaluationSchema(),
        );

        $criteria = $this->hydrateCriteria(
            $result['criteria'] ?? null
        );

        $score = $this->integerBetween(
            $result['readiness_score'] ?? null,
            0,
            100,
            'The readiness score was invalid.'
        );

        $ready = $result['is_ready'] ?? null;

        if (!is_bool($ready)) {
            throw new \RuntimeException(
                'The readiness decision was invalid.'
            );
        }

        $recommendation = $this->nullableString(
            $result['recommendation'] ?? null
        );

        return new ProgressEvaluation(
            criteria: $criteria,
            readinessScore: $score,
            ready: $ready,
            recommendation: $recommendation,
        );
    }

    private function serializeMemory(
        PillarMemory $memory,
    ): string {
        $payload = [
            'pillar' => $memory->pillar()->value,
            'status' => $memory->status()->value,
            'revision' => $memory->revision(),
            'memory_data' => $memory->data(),
        ];

        return implode(
            "\n",
            [
                '# Confirmed Story Memory',
                '',
                json_encode(
                    $payload,
                    JSON_THROW_ON_ERROR
                    | JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                ),
            ]
        );
    }

    /**
     * @return list<ProgressCriterion>
     */
    private function hydrateCriteria(
        mixed $value,
    ): array {
        if (!is_array($value)) {
            throw new \RuntimeException(
                'Progress criteria were not returned.'
            );
        }

        $criteriaByKey = [];

        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = trim(
                (string) ($item['key'] ?? '')
            );

            if (
                !array_key_exists(
                    $key,
                    self::CRITERION_LABELS
                )
            ) {
                throw new \RuntimeException(
                    'An unknown Story criterion was returned.'
                );
            }

            if (isset($criteriaByKey[$key])) {
                throw new \RuntimeException(
                    'A Story criterion was returned more than once.'
                );
            }

            $status = CriterionStatus::tryFrom(
                (string) ($item['status'] ?? '')
            );

            if ($status === null) {
                throw new \RuntimeException(
                    'A Story criterion status was invalid.'
                );
            }

            $criteriaByKey[$key] =
                new ProgressCriterion(
                    key: $key,

                    label:
                        self::CRITERION_LABELS[$key],

                    status: $status,

                    evidence: $this->nullableString(
                        $item['evidence'] ?? null
                    ),

                    guidance: $this->nullableString(
                        $item['guidance'] ?? null
                    ),
                );
        }

        foreach (
            self::CRITERION_LABELS
            as $key => $label
        ) {
            if (!isset($criteriaByKey[$key])) {
                throw new \RuntimeException(
                    'The evaluation omitted the '
                    . $label
                    . ' criterion.'
                );
            }
        }

        $ordered = [];

        foreach (
            array_keys(self::CRITERION_LABELS)
            as $key
        ) {
            $ordered[] = $criteriaByKey[$key];
        }

        return $ordered;
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluationSchema(): array
    {
        return [
            'type' => 'object',

            'properties' => [
                'criteria' => [
                    'type' => 'array',

                    'minItems' => 6,
                    'maxItems' => 6,

                    'items' => [
                        'type' => 'object',

                        'properties' => [
                            'key' => [
                                'type' => 'string',

                                'enum' => array_keys(
                                    self::CRITERION_LABELS
                                ),
                            ],

                            'status' => [
                                'type' => 'string',

                                'enum' => [
                                    'missing',
                                    'emerging',
                                    'established',
                                ],
                            ],

                            'evidence' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],

                            'guidance' => [
                                'type' => [
                                    'string',
                                    'null',
                                ],
                            ],
                        ],

                        'required' => [
                            'key',
                            'status',
                            'evidence',
                            'guidance',
                        ],

                        'additionalProperties' => false,
                    ],
                ],

                'readiness_score' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],

                'is_ready' => [
                    'type' => 'boolean',
                ],

                'recommendation' => [
                    'type' => [
                        'string',
                        'null',
                    ],
                ],
            ],

            'required' => [
                'criteria',
                'readiness_score',
                'is_ready',
                'recommendation',
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

    private function integerBetween(
        mixed $value,
        int $minimum,
        int $maximum,
        string $errorMessage,
    ): int {
        if (!is_int($value)) {
            throw new \RuntimeException(
                $errorMessage
            );
        }

        if (
            $value < $minimum
            || $value > $maximum
        ) {
            throw new \RuntimeException(
                $errorMessage
            );
        }

        return $value;
    }
}