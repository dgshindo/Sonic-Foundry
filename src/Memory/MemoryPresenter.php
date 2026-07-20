<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

use SonicFoundry\Work\WorkPillar;

final class MemoryPresenter
{
    /**
     * Convert pillar memory into a stable UI view model.
     *
     * During Bundle C1A, Story retains its legacy named fields while
     * also exposing the new generic sections collection.
     *
     * @return array<string, mixed>
     */
    public function present(
        ?PillarMemory $memory,
        WorkPillar $pillar = WorkPillar::Story,
    ): array {
        if ($memory === null) {
            return $this->emptyView(
                $pillar
            );
        }

        return match ($memory->pillar()) {
            WorkPillar::Story =>
                $this->presentStory(
                    $memory
                ),

            WorkPillar::Emotion =>
                $this->presentEmotion(
                    $memory
                ),

            default =>
                $this->presentGeneric(
                    $memory
                ),
        };
    }

    /**
     * Present Story memory.
     *
     * The named Story fields remain available for the current Forge
     * renderer. The sections array is the new pillar-agnostic contract
     * that Bundle C1B will consume.
     *
     * @return array<string, mixed>
     */
    private function presentStory(
        PillarMemory $memory,
    ): array {
        $data = $memory->data();

        $summary = $this->textField(
            $data['summary'] ?? null,
            'No summary has been established.'
        );

        $themes = $this->listField(
            $data['themes'] ?? null,
            'No themes have been established.'
        );

        $perspective = $this->textField(
            $data['perspective'] ?? null,
            'Perspective has not been established.'
        );

        $coreTension = $this->textField(
            $data['core_tension'] ?? null,
            'Core tension has not been established.'
        );

        $keySubjects = $this->listField(
            $data['key_subjects'] ?? null,
            'No key subjects have been established.'
        );

        $listenerTakeaway = $this->textField(
            $data['listener_takeaway'] ?? null,
            'Listener takeaway has not been established.'
        );

        return array_merge(
            $this->baseView(
                $memory
            ),
            [
                /*
                 * Legacy Story view keys.
                 *
                 * These remain until Bundle C1B replaces the
                 * Story-specific Forge markup.
                 */
                'summary' => $summary,
                'themes' => $themes,
                'perspective' => $perspective,
                'coreTension' => $coreTension,
                'keySubjects' => $keySubjects,
                'listenerTakeaway' =>
                    $listenerTakeaway,

                /*
                 * New generic presentation contract.
                 */
                'sections' => [
                    $this->textSection(
                        key: 'summary',
                        label: 'Summary',
                        field: $summary,
                    ),

                    $this->listSection(
                        key: 'themes',
                        label: 'Themes',
                        field: $themes,
                    ),

                    $this->textSection(
                        key: 'perspective',
                        label: 'Perspective',
                        field: $perspective,
                    ),

                    $this->textSection(
                        key: 'coreTension',
                        label: 'Core Tension',
                        field: $coreTension,
                    ),

                    $this->listSection(
                        key: 'keySubjects',
                        label: 'Key Subjects',
                        field: $keySubjects,
                    ),

                    $this->textSection(
                        key: 'listenerTakeaway',
                        label: 'Listener Takeaway',
                        field: $listenerTakeaway,
                    ),
                ],
            ]
        );
    }

    /**
     * Present Emotion memory through the generic sections contract.
     *
     * @return array<string, mixed>
     */
    private function presentEmotion(
        PillarMemory $memory,
    ): array {
        $data = $memory->data();

        $emotionalCore = $this->textField(
            $data['emotional_core'] ?? null,
            'The emotional core has not been established.'
        );

        $startingEmotion = $this->textField(
            $data['starting_emotion'] ?? null,
            'The starting emotional state has not been established.'
        );

        $endingEmotion = $this->textField(
            $data['ending_emotion'] ?? null,
            'The ending emotional state has not been established.'
        );

        $emotionalArc = $this->textField(
            $data['emotional_arc'] ?? null,
            'The emotional arc has not been established.'
        );

        $emotionalStakes = $this->textField(
            $data['emotional_stakes'] ?? null,
            'The emotional stakes have not been established.'
        );

        $desiredListenerFeeling = $this->textField(
            $data['desired_listener_feeling'] ?? null,
            'The desired listener feeling has not been established.'
        );

        $emotionalContrasts = $this->listField(
            $data['emotional_contrasts'] ?? null,
            'No emotional contrasts have been established.'
        );

        $emotionalTouchstones = $this->listField(
            $data['emotional_touchstones'] ?? null,
            'No emotional touchstones have been established.'
        );

        return array_merge(
            $this->baseView(
                $memory
            ),
            [
                'document' =>
                    $memory->data(),

                'sections' => [
                    $this->textSection(
                        key: 'emotionalCore',
                        label: 'Emotional Core',
                        field: $emotionalCore,
                    ),

                    $this->textSection(
                        key: 'startingEmotion',
                        label: 'Starting Emotion',
                        field: $startingEmotion,
                    ),

                    $this->textSection(
                        key: 'endingEmotion',
                        label: 'Ending Emotion',
                        field: $endingEmotion,
                    ),

                    $this->textSection(
                        key: 'emotionalArc',
                        label: 'Emotional Arc',
                        field: $emotionalArc,
                    ),

                    $this->textSection(
                        key: 'emotionalStakes',
                        label: 'Emotional Stakes',
                        field: $emotionalStakes,
                    ),

                    $this->textSection(
                        key: 'desiredListenerFeeling',
                        label: 'Desired Listener Feeling',
                        field: $desiredListenerFeeling,
                    ),

                    $this->listSection(
                        key: 'emotionalContrasts',
                        label: 'Emotional Contrasts',
                        field: $emotionalContrasts,
                    ),

                    $this->listSection(
                        key: 'emotionalTouchstones',
                        label: 'Emotional Touchstones',
                        field: $emotionalTouchstones,
                    ),
                ],
            ]
        );
    }

    /**
     * Temporary generic representation for future pillars.
     *
     * Emotion-specific sections will be introduced in Bundle C1C.
     *
     * @return array<string, mixed>
     */
    private function presentGeneric(
        PillarMemory $memory,
    ): array {
        return array_merge(
            $this->baseView(
                $memory
            ),
            [
                'document' =>
                    $memory->data(),

                'sections' => [],
            ]
        );
    }

    /**
     * Shared persisted-memory metadata.
     *
     * @return array<string, mixed>
     */
    private function baseView(
        PillarMemory $memory,
    ): array {
        return [
            'exists' => true,

            'id' => $memory->id(),

            'workId' =>
                $memory->workId(),

            'pillar' => [
                'value' =>
                    $memory->pillar()->value,

                'label' =>
                    $memory->pillar()->label(),
            ],

            'status' => [
                'value' =>
                    $memory->status()->value,

                'label' =>
                    $memory->statusLabel(),
            ],

            'confidence' => [
                'value' =>
                    $memory->confidence(),

                'display' =>
                    $this->displayConfidence(
                        $memory->confidence()
                    ),

                'percentage' =>
                    $memory->confidence() !== null
                        ? (int) round(
                            $memory->confidence()
                            * 100
                        )
                        : null,
            ],

            'revision' =>
                $memory->revision(),

            'canConfirm' =>
                $memory->isProposed(),

            'isProposed' =>
                $memory->isProposed(),

            'isConfirmed' =>
                $memory->isConfirmed(),

            'schemaVersion' =>
                $memory->schemaVersion(),

            'createdAt' => [
                'iso' => $memory
                    ->createdAt()
                    ->format(DATE_ATOM),

                'display' => $memory
                    ->createdAt()
                    ->format(
                        'M j, Y \a\t g:i A'
                    ),
            ],

            'updatedAt' => [
                'iso' => $memory
                    ->updatedAt()
                    ->format(DATE_ATOM),

                'display' => $memory
                    ->updatedAt()
                    ->format(
                        'M j, Y \a\t g:i A'
                    ),
            ],
        ];
    }

    /**
     * Return the empty presentation for a pillar.
     *
     * Story retains its named fields for backward compatibility.
     *
     * @return array<string, mixed>
     */
    private function emptyView(
        WorkPillar $pillar,
    ): array {
        $base = [
            'exists' => false,

            'id' => null,

            'workId' => null,

            'pillar' => [
                'value' =>
                    $pillar->value,

                'label' =>
                    $pillar->label(),
            ],

            'status' => [
                'value' => 'empty',

                'label' =>
                    'Not Yet Established',
            ],

            'confidence' => [
                'value' => null,
                'display' => 'Not available',
                'percentage' => null,
            ],

            'revision' => null,

            'canConfirm' => false,

            'isProposed' => false,

            'isConfirmed' => false,

            'schemaVersion' => null,

            'createdAt' => [
                'iso' => null,
                'display' => null,
            ],

            'updatedAt' => [
                'iso' => null,
                'display' => null,
            ],
        ];

        if ($pillar === WorkPillar::Emotion) {
            $emotionalCore = $this->textField(
                null,
                'The emotional core will appear after it is discussed and proposed.'
            );

            $startingEmotion = $this->textField(
                null,
                'The starting emotional state has not yet been established.'
            );

            $endingEmotion = $this->textField(
                null,
                'The ending emotional state has not yet been established.'
            );

            $emotionalArc = $this->textField(
                null,
                'The emotional journey has not yet been established.'
            );

            $emotionalStakes = $this->textField(
                null,
                'The emotional stakes have not yet been established.'
            );

            $desiredListenerFeeling = $this->textField(
                null,
                'The intended listener feeling has not yet been established.'
            );

            $emotionalContrasts = $this->listField(
                [],
                'Emotional contrasts will appear after they are identified.'
            );

            $emotionalTouchstones = $this->listField(
                [],
                'Emotional touchstones will appear after they are identified.'
            );

            return array_merge(
                $base,
                [
                    'document' => [],

                    'sections' => [
                        $this->textSection(
                            key: 'emotionalCore',
                            label: 'Emotional Core',
                            field: $emotionalCore,
                        ),

                        $this->textSection(
                            key: 'startingEmotion',
                            label: 'Starting Emotion',
                            field: $startingEmotion,
                        ),

                        $this->textSection(
                            key: 'endingEmotion',
                            label: 'Ending Emotion',
                            field: $endingEmotion,
                        ),

                        $this->textSection(
                            key: 'emotionalArc',
                            label: 'Emotional Arc',
                            field: $emotionalArc,
                        ),

                        $this->textSection(
                            key: 'emotionalStakes',
                            label: 'Emotional Stakes',
                            field: $emotionalStakes,
                        ),

                        $this->textSection(
                            key: 'desiredListenerFeeling',
                            label: 'Desired Listener Feeling',
                            field: $desiredListenerFeeling,
                        ),

                        $this->listSection(
                            key: 'emotionalContrasts',
                            label: 'Emotional Contrasts',
                            field: $emotionalContrasts,
                        ),

                        $this->listSection(
                            key: 'emotionalTouchstones',
                            label: 'Emotional Touchstones',
                            field: $emotionalTouchstones,
                        ),
                    ],
                ]
            );
        }

        if ($pillar !== WorkPillar::Story) {
            return array_merge(
                $base,
                [
                    'document' => [],
                    'sections' => [],
                ]
            );
        }

        $summary = $this->textField(
            null,
            'No Story summary has been proposed yet.'
        );

        $themes = $this->listField(
            [],
            'Themes will appear after they are discussed and proposed.'
        );

        $perspective = $this->textField(
            null,
            'The narrative perspective has not yet been established.'
        );

        $coreTension = $this->textField(
            null,
            'The central tension has not yet been established.'
        );

        $keySubjects = $this->listField(
            [],
            'Key subjects will appear after they are identified.'
        );

        $listenerTakeaway = $this->textField(
            null,
            'The intended listener takeaway has not yet been established.'
        );

        return array_merge(
            $base,
            [
                /*
                 * Legacy Story view keys.
                 */
                'summary' => $summary,
                'themes' => $themes,
                'perspective' => $perspective,
                'coreTension' => $coreTension,
                'keySubjects' => $keySubjects,
                'listenerTakeaway' =>
                    $listenerTakeaway,

                /*
                 * New generic sections contract.
                 */
                'sections' => [
                    $this->textSection(
                        key: 'summary',
                        label: 'Summary',
                        field: $summary,
                    ),

                    $this->listSection(
                        key: 'themes',
                        label: 'Themes',
                        field: $themes,
                    ),

                    $this->textSection(
                        key: 'perspective',
                        label: 'Perspective',
                        field: $perspective,
                    ),

                    $this->textSection(
                        key: 'coreTension',
                        label: 'Core Tension',
                        field: $coreTension,
                    ),

                    $this->listSection(
                        key: 'keySubjects',
                        label: 'Key Subjects',
                        field: $keySubjects,
                    ),

                    $this->textSection(
                        key: 'listenerTakeaway',
                        label: 'Listener Takeaway',
                        field: $listenerTakeaway,
                    ),
                ],
            ]
        );
    }

    /**
     * Create a generic text section.
     *
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>
     */
    private function textSection(
        string $key,
        string $label,
        array $field,
    ): array {
        return [
            'type' => 'text',
            'key' => $key,
            'label' => $label,
            'value' => $field,
        ];
    }

    /**
     * Create a generic list section.
     *
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>
     */
    private function listSection(
        string $key,
        string $label,
        array $field,
    ): array {
        return [
            'type' => 'list',
            'key' => $key,
            'label' => $label,
            'value' => $field,
        ];
    }

    /**
     * Normalize a text value for display.
     *
     * @return array<string, mixed>
     */
    private function textField(
        mixed $value,
        string $fallback,
    ): array {
        $normalized = is_string($value)
            ? trim($value)
            : '';

        return [
            'value' =>
                $normalized !== ''
                    ? $normalized
                    : null,

            'display' =>
                $normalized !== ''
                    ? $normalized
                    : $fallback,

            'hasValue' =>
                $normalized !== '',
        ];
    }

    /**
     * Normalize a list value for display.
     *
     * @return array<string, mixed>
     */
    private function listField(
        mixed $value,
        string $fallback,
    ): array {
        $normalized = [];

        if (is_array($value)) {
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
        }

        $values = array_values(
            $normalized
        );

        return [
            'values' => $values,

            'display' =>
                $values !== []
                    ? implode(', ', $values)
                    : $fallback,

            'hasValues' =>
                $values !== [],
        ];
    }

    private function displayConfidence(
        ?float $confidence,
    ): string {
        if ($confidence === null) {
            return 'Not available';
        }

        return number_format(
            $confidence * 100,
            1
        ) . '%';
    }
}