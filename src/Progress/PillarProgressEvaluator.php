<?php
declare(strict_types=1);

namespace SonicFoundry\Progress;

use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Memory\PillarMemory;
use SonicFoundry\Memory\PillarMemoryService;
use SonicFoundry\Pillars\Contracts\ProgressDefinition;
use SonicFoundry\Pillars\Registry\PillarRegistry;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class PillarProgressEvaluator
{
    public function __construct(
        private OpenAIClient $openAI,
        private PromptAssembler $prompts,
        private PillarMemoryService $memory,
        private WorkService $works,
        private PillarRegistry $pillars,
    ) {
    }

    public function evaluate(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
    ): ProgressEvaluation {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        $pillarDefinition = $this->pillars
            ->definition(
                $pillar
            );

        $progressDefinition =
            $pillarDefinition->progress();

        $memory = $this->memory
            ->memoryForWork(
                user: $user,
                workId: $work->id(),
                pillarValue: $pillar->value,
            );

        if (
            $memory === null
            || !$memory->isConfirmed()
        ) {
            throw new \DomainException(
                $pillar->label()
                . ' progress requires confirmed Creative Memory.'
            );
        }

        $instructions = $this->prompts
            ->assemble(
                promptPaths: [
                    $progressDefinition
                        ->promptPath(),
                ],

                variables: [
                    'work_title' =>
                        $work->title(),

                    'work_type' =>
                        $work->typeLabel(),

                    'pillar_name' =>
                        $pillar->label(),
                ],
            );

        $result = $this->openAI
            ->structuredResponse(
                instructions: $instructions,

                input: [
                    [
                        'role' => 'user',

                        'content' =>
                            $this->serializeMemory(
                                memory: $memory,
                                definition:
                                    $progressDefinition,
                            ),
                    ],
                ],

                schemaName:
                    $pillar->value
                    . '_progress_evaluation',

                schema:
                    $this->evaluationSchema(
                        $progressDefinition
                    ),
            );

        return $this->hydrateEvaluation(
            result: $result,
            definition: $progressDefinition,
        );
    }

    private function resolvePillar(
        string $pillarValue,
    ): WorkPillar {
        $pillar = WorkPillar::tryFrom(
            mb_strtolower(
                trim($pillarValue)
            )
        );

        if ($pillar === null) {
            throw new \DomainException(
                'A valid creative pillar is required.'
            );
        }

        if (!$this->pillars->has($pillar)) {
            throw new \DomainException(
                'That creative pillar has no registered definition.'
            );
        }

        return $pillar;
    }

    private function serializeMemory(
        PillarMemory $memory,
        ProgressDefinition $definition,
    ): string {
        $criteria = [];

        foreach (
            $definition->criteria()
            as $criterion
        ) {
            $criteria[] = [
                'key' =>
                    $criterion->key(),

                'label' =>
                    $criterion->label(),

                'description' =>
                    $criterion->description(),
            ];
        }

        $payload = [
            'pillar' =>
                $memory->pillar()->value,

            'status' =>
                $memory->status()->value,

            'revision' =>
                $memory->revision(),

            'schema_version' =>
                $memory->schemaVersion(),

            'completion_threshold' =>
                $definition
                    ->completionThreshold(),

            'criteria' =>
                $criteria,

            'memory_data' =>
                $memory->data(),
        ];

        return implode(
            "\n",
            [
                '# Confirmed Creative Memory',
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
     * @param array<string, mixed> $result
     */
    private function hydrateEvaluation(
        array $result,
        ProgressDefinition $definition,
    ): ProgressEvaluation {
        $criteria = $this->hydrateCriteria(
            value:
                $result['criteria']
                ?? null,

            definition:
                $definition,
        );

        $score = $this->integerBetween(
            value:
                $result['readiness_score']
                ?? null,

            minimum: 0,
            maximum: 100,

            errorMessage:
                'The readiness score was invalid.',
        );

        $modelReady =
            $result['is_ready']
            ?? null;

        if (!is_bool($modelReady)) {
            throw new \RuntimeException(
                'The readiness decision was invalid.'
            );
        }

        /*
         * The model must recommend readiness and the declared
         * completion threshold must also be satisfied.
         */
        $ready = (
            $modelReady
            && $score >=
                $definition
                    ->completionThreshold()
        );

        return new ProgressEvaluation(
            criteria: $criteria,

            readinessScore:
                $score,

            ready:
                $ready,

            recommendation:
                $this->nullableString(
                    $result['recommendation']
                    ?? null
                ),
        );
    }

    /**
     * @return list<ProgressCriterion>
     */
    private function hydrateCriteria(
        mixed $value,
        ProgressDefinition $definition,
    ): array {
        if (!is_array($value)) {
            throw new \RuntimeException(
                'Progress criteria were not returned.'
            );
        }

        $definitionsByKey = [];

        foreach (
            $definition->criteria()
            as $criterionDefinition
        ) {
            $definitionsByKey[
                $criterionDefinition->key()
            ] = $criterionDefinition;
        }

        $criteriaByKey = [];

        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = trim(
                (string) (
                    $item['key']
                    ?? ''
                )
            );

            $criterionDefinition =
                $definitionsByKey[$key]
                ?? null;

            if ($criterionDefinition === null) {
                throw new \RuntimeException(
                    'An unknown progress criterion was returned.'
                );
            }

            if (isset($criteriaByKey[$key])) {
                throw new \RuntimeException(
                    'A progress criterion was returned more than once.'
                );
            }

            $status = CriterionStatus::tryFrom(
                (string) (
                    $item['status']
                    ?? ''
                )
            );

            if ($status === null) {
                throw new \RuntimeException(
                    'A progress criterion status was invalid.'
                );
            }

            $criteriaByKey[$key] =
                new ProgressCriterion(
                    key:
                        $criterionDefinition
                            ->key(),

                    label:
                        $criterionDefinition
                            ->label(),

                    status:
                        $status,

                    evidence:
                        $this->nullableString(
                            $item['evidence']
                            ?? null
                        ),

                    guidance:
                        $this->nullableString(
                            $item['guidance']
                            ?? null
                        ),
                );
        }

        $ordered = [];

        foreach (
            $definition->criteria()
            as $criterionDefinition
        ) {
            $key =
                $criterionDefinition->key();

            if (!isset($criteriaByKey[$key])) {
                throw new \RuntimeException(
                    'The evaluation omitted the '
                    . $criterionDefinition->label()
                    . ' criterion.'
                );
            }

            $ordered[] =
                $criteriaByKey[$key];
        }

        return $ordered;
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluationSchema(
        ProgressDefinition $definition,
    ): array {
        $criterionKeys = [];

        foreach (
            $definition->criteria()
            as $criterion
        ) {
            $criterionKeys[] =
                $criterion->key();
        }

        $criterionCount =
            count($criterionKeys);

        return [
            'type' => 'object',

            'properties' => [
                'criteria' => [
                    'type' => 'array',

                    'minItems' =>
                        $criterionCount,

                    'maxItems' =>
                        $criterionCount,

                    'items' => [
                        'type' => 'object',

                        'properties' => [
                            'key' => [
                                'type' => 'string',
                                'enum' => $criterionKeys,
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

                        'additionalProperties' =>
                            false,
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