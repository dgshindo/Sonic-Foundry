<?php
declare(strict_types=1);

namespace SonicFoundry\Progress;

use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class PillarProgressService
{
    public function __construct(
        private readonly PillarProgressRepository $progress,
        private readonly WorkService $works,
    ) {
    }

    public function progressForWork(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
    ): ?PillarProgress {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        return $this->progress
            ->findByWorkAndPillar(
                workId: $work->id(),
                pillar: $pillar,
            );
    }

    public function recordEvaluation(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
        ProgressEvaluation $evaluation,
    ): PillarProgress {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        return $this->progress
            ->saveEvaluation(
                workId: $work->id(),
                pillar: $pillar,
                evaluation: $evaluation,
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

        return $pillar;
    }
}