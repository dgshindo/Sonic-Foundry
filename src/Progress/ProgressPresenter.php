<?php
declare(strict_types=1);

namespace SonicFoundry\Progress;

final class ProgressPresenter
{
    /**
     * Convert persisted progress into a stable Forge view model.
     *
     * @return array<string, mixed>
     */
    public function present(
        ?PillarProgress $progress,
    ): array {
        if ($progress === null) {
            return $this->emptyView();
        }

        return [
            'exists' => true,

            'id' => $progress->id(),

            'workId' => $progress->workId(),

            'pillar' => [
                'value' => $progress->pillar()->value,
                'label' => $progress->pillar()->label(),
            ],

            'status' => [
                'value' => $progress->status()->value,
                'label' => $progress->statusLabel(),
            ],

            'readinessScore' =>
                $progress->readinessScore(),

            'readinessDisplay' =>
                $progress->readinessScore() . '%',

            'isReady' =>
                $progress->isReady(),

            'criteria' =>
                $this->presentCriteria(
                    $progress->criteria()
                ),

            'recommendation' => [
                'value' =>
                    $progress->recommendation(),

                'display' =>
                    $this->displayRecommendation(
                        $progress
                            ->recommendation()
                    ),

                'hasValue' =>
                    $progress->recommendation()
                    !== null,
            ],

            'revision' =>
                $progress->revision(),

            'evaluatedAt' => [
                'iso' => $progress
                    ->evaluatedAt()
                    ->format(DATE_ATOM),

                'display' => $progress
                    ->evaluatedAt()
                    ->format(
                        'M j, Y \a\t g:i A'
                    ),
            ],

            'updatedAt' => [
                'iso' => $progress
                    ->updatedAt()
                    ->format(DATE_ATOM),

                'display' => $progress
                    ->updatedAt()
                    ->format(
                        'M j, Y \a\t g:i A'
                    ),
            ],
        ];
    }

    /**
     * @param list<ProgressCriterion> $criteria
     *
     * @return list<array<string, mixed>>
     */
    private function presentCriteria(
        array $criteria,
    ): array {
        $view = [];

        foreach ($criteria as $criterion) {
            $view[] = [
                'key' =>
                    $criterion->key(),

                'label' =>
                    $criterion->label(),

                'status' => [
                    'value' =>
                        $criterion
                            ->status()
                            ->value,

                    'label' =>
                        $criterion
                            ->statusLabel(),
                ],

                'symbol' =>
                    $this->criterionSymbol(
                        $criterion->status()
                    ),

                'evidence' =>
                    $criterion->evidence(),

                'guidance' =>
                    $criterion->guidance(),

                'isEstablished' =>
                    $criterion
                        ->isEstablished(),
            ];
        }

        return $view;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyView(): array
    {
        return [
            'exists' => false,

            'id' => null,
            'workId' => null,

            'pillar' => [
                'value' => null,
                'label' => null,
            ],

            'status' => [
                'value' => 'unevaluated',
                'label' => 'Not Yet Evaluated',
            ],

            'readinessScore' => null,

            'readinessDisplay' => '—',

            'isReady' => false,

            'criteria' => [],

            'recommendation' => [
                'value' => null,

                'display' => (
                    'Confirm the Story understanding '
                    . 'before evaluating readiness.'
                ),

                'hasValue' => false,
            ],

            'revision' => null,

            'evaluatedAt' => [
                'iso' => null,
                'display' => null,
            ],

            'updatedAt' => [
                'iso' => null,
                'display' => null,
            ],
        ];
    }

    private function criterionSymbol(
        CriterionStatus $status,
    ): string {
        return match ($status) {
            CriterionStatus::Missing => '○',
            CriterionStatus::Emerging => '◐',
            CriterionStatus::Established => '✓',
        };
    }

    private function displayRecommendation(
        ?string $recommendation,
    ): string {
        if ($recommendation === null) {
            return (
                'No readiness recommendation '
                . 'is currently available.'
            );
        }

        $recommendation = trim(
            $recommendation
        );

        return $recommendation !== ''
            ? $recommendation
            : (
                'No readiness recommendation '
                . 'is currently available.'
            );
    }
}