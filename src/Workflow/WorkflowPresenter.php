<?php
declare(strict_types=1);

namespace SonicFoundry\Workflow;

use SonicFoundry\Work\WorkPillar;

final class WorkflowPresenter
{
    /**
     * @param list<PillarWorkflow> $workflow
     *
     * @return array<string, array<string, mixed>>
     */
    public function present(array $workflow): array
    {
        $byPillar = [];

        foreach ($workflow as $item) {
            $byPillar[$item->pillar()->value] = [
                'pillar' => [
                    'value' => $item->pillar()->value,
                    'label' => $item->pillar()->label(),
                ],

                'status' => [
                    'value' => $item->status()->value,
                    'label' => $item->statusLabel(),
                ],

                'isLocked' => $item->isLocked(),
                'isAvailable' => $item->isAvailable(),
                'isCompleted' => $item->isCompleted(),

                'revision' => $item->revision(),

                'unlockedAt' => $item->unlockedAt() !== null
                    ? [
                        'iso' => $item
                            ->unlockedAt()
                            ?->format(DATE_ATOM),

                        'display' => $item
                            ->unlockedAt()
                            ?->format('M j, Y \a\t g:i A'),
                    ]
                    : null,

                'completedAt' => $item->completedAt() !== null
                    ? [
                        'iso' => $item
                            ->completedAt()
                            ?->format(DATE_ATOM),

                        'display' => $item
                            ->completedAt()
                            ?->format('M j, Y \a\t g:i A'),
                    ]
                    : null,
            ];
        }

        foreach (WorkPillar::cases() as $pillar) {
            if (isset($byPillar[$pillar->value])) {
                continue;
            }

            $byPillar[$pillar->value] = [
                'pillar' => [
                    'value' => $pillar->value,
                    'label' => $pillar->label(),
                ],

                'status' => [
                    'value' => 'locked',
                    'label' => 'Locked',
                ],

                'isLocked' => true,
                'isAvailable' => false,
                'isCompleted' => false,
                'revision' => null,
                'unlockedAt' => null,
                'completedAt' => null,
            ];
        }

        return $byPillar;
    }
}