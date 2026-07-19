<?php
declare(strict_types=1);

namespace SonicFoundry\Workflow;

use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Progress\PillarProgressService;
use SonicFoundry\Progress\ProgressStatus;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class PillarWorkflowService
{
    public function __construct(
        private readonly PillarWorkflowRepository $workflows,
        private readonly PillarProgressService $progress,
        private readonly WorkService $works,
    ) {
    }

    /**
     * @return list<PillarWorkflow>
     */
    public function workflowForWork(
        AuthenticatedUser $user,
        int $workId,
    ): array {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $this->workflows->initializeForWork(
            $work->id()
        );

        return $this->workflows->findForWork(
            $work->id()
        );
    }

    public function pillarForWork(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
    ): PillarWorkflow {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        $this->workflows->initializeForWork(
            $work->id()
        );

        $workflow = $this->workflows
            ->findByWorkAndPillar(
                workId: $work->id(),
                pillar: $pillar,
            );

        if ($workflow === null) {
            throw new \RuntimeException(
                'Pillar workflow could not be loaded.'
            );
        }

        return $workflow;
    }

    public function complete(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
    ): PillarWorkflow {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        $this->workflows->initializeForWork(
            $work->id()
        );

        $current = $this->workflows
            ->findByWorkAndPillar(
                workId: $work->id(),
                pillar: $pillar,
            );

        if ($current === null) {
            throw new \RuntimeException(
                'Current pillar workflow could not be loaded.'
            );
        }

        if ($current->isCompleted()) {
            throw new \DomainException(
                'This pillar is already complete.'
            );
        }

        if (!$current->isAvailable()) {
            throw new \DomainException(
                'This pillar is not currently available.'
            );
        }

        $progress = $this->progress
            ->progressForWork(
                user: $user,
                workId: $work->id(),
                pillarValue: $pillar->value,
            );

        if (
            $progress === null
            || !$progress->isReady()
            || $progress->status()
                === ProgressStatus::Completed
        ) {
            throw new \DomainException(
                'This pillar is not yet ready for completion.'
            );
        }

        $nextPillar = $this->nextPillar(
            $pillar
        );

        $nextWorkflow = $nextPillar !== null
            ? $this->workflows
                ->findByWorkAndPillar(
                    workId: $work->id(),
                    pillar: $nextPillar,
                )
            : null;

        return $this->workflows
            ->completeAndUnlock(
                current: $current,
                next: $nextWorkflow,
            );
    }

    private function nextPillar(
        WorkPillar $pillar,
    ): ?WorkPillar {
        $pillars = WorkPillar::cases();

        foreach ($pillars as $index => $candidate) {
            if ($candidate !== $pillar) {
                continue;
            }

            return $pillars[$index + 1]
                ?? null;
        }

        return null;
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

        return $pillar;
    }
}