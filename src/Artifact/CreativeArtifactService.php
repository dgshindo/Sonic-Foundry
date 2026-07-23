<?php
declare(strict_types=1);

namespace SonicFoundry\Artifact;

use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Work\WorkService;

final class CreativeArtifactService
{
    public function __construct(
        private CreativeArtifactRepository $artifacts,
        private WorkService $works,
    ) {
    }

    public function artifactForWork(
        AuthenticatedUser $user,
        int $workId,
        CreativeArtifactType $type,
    ): ?CreativeArtifact {
        $work = $this->works
            ->findOwnedWork(
                workId: $workId,
                user: $user,
            );

        return $this->artifacts
            ->findByWorkAndType(
                workId: $work->id(),
                type: $type,
            );
    }

    public function save(
        AuthenticatedUser $user,
        int $workId,
        CreativeArtifactType $type,
        string $title,
        string $content,
    ): CreativeArtifact {
        $work = $this->works
            ->findOwnedWork(
                workId: $workId,
                user: $user,
            );

        return $this->artifacts
            ->save(
                workId: $work->id(),
                type: $type,
                title: $title,
                content: $content,
            );
    }
}