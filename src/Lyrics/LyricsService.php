<?php
declare(strict_types=1);

namespace SonicFoundry\Lyrics;

use SonicFoundry\Artifact\CreativeArtifact;
use SonicFoundry\Artifact\CreativeArtifactService;
use SonicFoundry\Artifact\CreativeArtifactType;
use SonicFoundry\Auth\AuthenticatedUser;

final class LyricsService
{
    public function __construct(
        private LyricsGenerator $generator,
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
                type: CreativeArtifactType::Lyrics,
                title: $generated['title'],
                content: $generated['content'],
            );
    }

    public function lyricsForWork(
        AuthenticatedUser $user,
        int $workId,
    ): ?CreativeArtifact {
        return $this->artifacts
            ->artifactForWork(
                user: $user,
                workId: $workId,
                type: CreativeArtifactType::Lyrics,
            );
    }
}