<?php
declare(strict_types=1);

namespace SonicFoundry\Work;

use DomainException;
use SonicFoundry\Auth\AuthenticatedUser;

final class WorkService
{
    public function __construct(
        private readonly WorkRepository $works,
    ) {
    }

    public function create(
        AuthenticatedUser $user,
        string $title,
        string $workType,
    ): Work {
        $normalizedTitle = trim($title);

        if ($normalizedTitle === '') {
            $normalizedTitle = 'Untitled Work';
        }

        if (mb_strlen($normalizedTitle) > 180) {
            throw new DomainException(
                'The work title may not exceed 180 characters.'
            );
        }

        $type = WorkType::tryFrom(
            mb_strtolower(trim($workType))
        );

        if (!$type) {
            throw new DomainException(
                'Select a valid work type.'
            );
        }

        return $this->works->create(
            userId: $user->id(),
            title: $normalizedTitle,
            type: $type,
        );
    }

    public function findOwnedWork(
        int $workId,
        AuthenticatedUser $user,
    ): Work {
        if ($workId < 1) {
            throw new DomainException(
                'A valid work is required.'
            );
        }

        $work = $this->works->findByIdForUser(
            workId: $workId,
            userId: $user->id(),
        );

        if (!$work) {
            throw new DomainException(
                'The requested work could not be found.'
            );
        }

        return $work;
    }

    /**
     * @return list<Work>
     */
    public function listForUser(
        AuthenticatedUser $user
    ): array {
        return $this->works->findAllByUser(
            $user->id()
        );
    }
}