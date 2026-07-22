<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

use SonicFoundry\Pillars\Contracts\MemoryDefinition;
use SonicFoundry\Pillars\Registry\PillarRegistry;
use SonicFoundry\Pillars\Support\MemoryFieldDefinition;
use SonicFoundry\Pillars\Support\MemoryFieldType;
use SonicFoundry\Work\WorkPillar;

final class MemoryPresenter
{
    public function __construct(
        private PillarRegistry $pillars,
    ) {
    }

    /**
     * Convert pillar memory into a stable, definition-driven
     * presentation model.
     *
     * When memory is null, the pillar argument identifies which
     * definition supplies the empty-state fields.
     *
     * @return array<string, mixed>
     */
    public function present(
        ?PillarMemory $memory,
        ?WorkPillar $pillar = null,
    ): array {
        $resolvedPillar = $memory?->pillar()
            ?? $pillar
            ?? WorkPillar::Story;

        $definition = $this->pillars
            ->definition(
                $resolvedPillar
            )
            ->memory();

        if ($memory === null) {
            return $this->emptyView(
                pillar: $resolvedPillar,
                definition: $definition,
            );
        }

        return array_merge(
            $this->baseView(
                $memory
            ),
            [
                'document' =>
                    $memory->data(),

                'sections' =>
                    $this->buildSections(
                        definition: $definition,
                        document: $memory->data(),
                    ),
            ]
        );
    }

    /**
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
     * @return array<string, mixed>
     */
    private function emptyView(
        WorkPillar $pillar,
        MemoryDefinition $definition,
    ): array {
        return [
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

            'document' => [],

            'sections' =>
                $this->buildSections(
                    definition: $definition,
                    document: [],
                ),
        ];
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<array<string, mixed>>
     */
    private function buildSections(
        MemoryDefinition $definition,
        array $document,
    ): array {
        $sections = [];

        foreach (
            $definition->fields()
            as $field
        ) {
            $sections[] =
                $this->buildSection(
                    field: $field,
                    value: $document[
                        $field->key()
                    ] ?? null,
                );
        }

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSection(
        MemoryFieldDefinition $field,
        mixed $value,
    ): array {
        return [
            'type' =>
                $field->type()->value,

            'key' =>
                $field->key(),

            'label' =>
                $field->label(),

            'value' => match (
                $field->type()
            ) {
                MemoryFieldType::Text =>
                    $this->textField(
                        value: $value,
                        fallback:
                            $field->emptyMessage(),
                    ),

                MemoryFieldType::List =>
                    $this->listField(
                        value: $value,
                        fallback:
                            $field->emptyMessage(),
                    ),
            },
        ];
    }

    /**
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