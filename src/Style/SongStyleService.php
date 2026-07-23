<?php
declare(strict_types=1);

namespace SonicFoundry\Style;

use SonicFoundry\Artifact\CreativeArtifact;
use SonicFoundry\Artifact\CreativeArtifactService;
use SonicFoundry\Artifact\CreativeArtifactType;
use SonicFoundry\Auth\AuthenticatedUser;

final class SongStyleService
{
    public function __construct(
        private SongStyleGenerator $generator,
        private CreativeArtifactService $artifacts,
    ) {
    }

    public function generateAndSave(
        AuthenticatedUser $user,
        int $workId,
    ): CreativeArtifact {
        $generated = $this->generator
            ->generate(
                user: $user,
                workId: $workId,
            );

        return $this->artifacts
            ->save(
                user: $user,
                workId: $workId,
                type:
                    CreativeArtifactType::SongStyleAddendum,
                title:
                    $generated['title'],
                content:
                    $generated['content'],
            );
    }

    public function songStyleForWork(
        AuthenticatedUser $user,
        int $workId,
    ): ?CreativeArtifact {
        return $this->artifacts
            ->artifactForWork(
                user: $user,
                workId: $workId,
                type:
                    CreativeArtifactType::SongStyleAddendum,
            );
    }
}