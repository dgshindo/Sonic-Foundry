<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class PillarMemoryService
{
    public function __construct(
        private readonly PillarMemoryRepository $memories,
        private readonly WorkService $works,
    ) {
    }

    public function memoryForWork(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
    ): ?PillarMemory {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        return $this->memories->findByWorkAndPillar(
            workId: $work->id(),
            pillar: $pillar,
        );
    }

    public function propose(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
        MemoryExtraction $extraction,
    ): PillarMemory {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        if ($extraction->isEmpty()) {
            throw new \DomainException(
                'The proposed memory contains no useful information.'
            );
        }

        return $this->memories->saveExtraction(
            workId: $work->id(),
            pillar: $pillar,
            extraction: $extraction,
            status: MemoryStatus::Proposed,
        );
    }

    public function confirm(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
    ): PillarMemory {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        $memory = $this->memories
            ->findByWorkAndPillar(
                workId: $work->id(),
                pillar: $pillar,
            );

        if ($memory === null) {
            throw new \DomainException(
                'There is no proposed memory to confirm.'
            );
        }

        if ($memory->isConfirmed()) {
            throw new \DomainException(
                'This memory has already been confirmed.'
            );
        }

        if (!$memory->isProposed()) {
            throw new \DomainException(
                'Only proposed memory may be confirmed.'
            );
        }

        return $this->memories->changeStatus(
            memory: $memory,
            status: MemoryStatus::Confirmed,
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