<?php
declare(strict_types=1);

namespace SonicFoundry\Style;

use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\Artifact\CreativeArtifact;
use SonicFoundry\Artifact\CreativeArtifactService;
use SonicFoundry\Artifact\CreativeArtifactType;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Memory\PillarMemory;
use SonicFoundry\Memory\PillarMemoryService;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class SongStyleGenerator
{
    public function __construct(
        private OpenAIClient $openAI,
        private PromptAssembler $prompts,
        private PillarMemoryService $memory,
        private CreativeArtifactService $artifacts,
        private WorkService $works,
    ) {
    }

    /**
     * @return array{
     *     title: string,
     *     content: string
     * }
     */
    public function generate(
        AuthenticatedUser $user,
        int $workId,
    ): array {
        $work = $this->works
            ->findOwnedWork(
                workId: $workId,
                user: $user,
            );

        $styleGuide = $this->requiredArtifact(
            user: $user,
            workId: $work->id(),
            type: CreativeArtifactType::StyleGuide,
            missingMessage:
                'A Producer Style Guide is required before generating a Song Style Addendum.',
        );

        $lyrics = $this->requiredArtifact(
            user: $user,
            workId: $work->id(),
            type: CreativeArtifactType::Lyrics,
            missingMessage:
                'Finished lyrics are required before generating a Song Style Addendum.',
        );

        $storyMemory = $this->requiredConfirmedMemory(
            user: $user,
            workId: $work->id(),
            pillar: WorkPillar::Story,
        );

        $emotionMemory = $this->requiredConfirmedMemory(
            user: $user,
            workId: $work->id(),
            pillar: WorkPillar::Emotion,
        );

        $identityMemory = $this->requiredConfirmedMemory(
            user: $user,
            workId: $work->id(),
            pillar: WorkPillar::Identity,
        );

        $soundMemory = $this->requiredConfirmedMemory(
            user: $user,
            workId: $work->id(),
            pillar: WorkPillar::Sound,
        );

        $impactMemory = $this->requiredConfirmedMemory(
            user: $user,
            workId: $work->id(),
            pillar: WorkPillar::Impact,
        );

        $instructions = $this->prompts
            ->assemble(
                promptPaths: [
                    'style/song-style-addendum.md',
                ],

                variables: [
                    'work_title' =>
                        $work->title(),

                    'work_type' =>
                        $work->typeLabel(),
                ],
            );

        $result = $this->openAI
            ->structuredResponse(
                instructions: $instructions,

                input: [
                    [
                        'role' => 'user',

                        'content' =>
                            $this->buildInputDocument(
                                workTitle:
                                    $work->title(),

                                workType:
                                    $work->typeLabel(),

                                styleGuide:
                                    $styleGuide,

                                lyrics:
                                    $lyrics,

                                storyMemory:
                                    $storyMemory,

                                emotionMemory:
                                    $emotionMemory,

                                identityMemory:
                                    $identityMemory,

                                soundMemory:
                                    $soundMemory,

                                impactMemory:
                                    $impactMemory,
                            ),
                    ],
                ],

                schemaName:
                    'song_style_addendum_generation',

                schema:
                    $this->responseSchema(),
            );

        return [
            'title' =>
                $this->requiredString(
                    value:
                        $result['title']
                        ?? null,

                    errorMessage:
                        'The generated Song Style Addendum did not include a title.',
                ),

            'content' =>
                $this->requiredString(
                    value:
                        $result['content']
                        ?? null,

                    errorMessage:
                        'The generated Song Style Addendum did not include content.',
                ),
        ];
    }

    private function requiredConfirmedMemory(
        AuthenticatedUser $user,
        int $workId,
        WorkPillar $pillar,
    ): PillarMemory {
        $memory = $this->memory
            ->memoryForWork(
                user: $user,
                workId: $workId,
                pillarValue: $pillar->value,
            );

        if (
            $memory === null
            || !$memory->isConfirmed()
        ) {
            throw new \DomainException(
                sprintf(
                    'Confirmed %s Creative Memory is required before generating a Song Style Addendum.',
                    $pillar->label()
                )
            );
        }

        return $memory;
    }

    private function requiredArtifact(
        AuthenticatedUser $user,
        int $workId,
        CreativeArtifactType $type,
        string $missingMessage,
    ): CreativeArtifact {
        $artifact = $this->artifacts
            ->artifactForWork(
                user: $user,
                workId: $workId,
                type: $type,
            );

        if ($artifact === null) {
            throw new \DomainException(
                $missingMessage
            );
        }

        return $artifact;
    }

    private function buildInputDocument(
        string $workTitle,
        string $workType,
        CreativeArtifact $styleGuide,
        CreativeArtifact $lyrics,
        PillarMemory $storyMemory,
        PillarMemory $emotionMemory,
        PillarMemory $identityMemory,
        PillarMemory $soundMemory,
        PillarMemory $impactMemory,
    ): string {
        $sections = [
            '# Work',
            '',
            '## Current Title',
            '',
            $workTitle,
            '',
            '## Type',
            '',
            $workType,
            '',
            '# Approved Producer Style Guide',
            '',
            '## ' . $styleGuide->title(),
            '',
            $styleGuide->content(),
            '',
            '# Finished Lyrics',
            '',
            '## ' . $lyrics->title(),
            '',
            $lyrics->content(),
            '',
        ];

        $this->appendMemorySection(
            sections: $sections,
            heading:
                'Confirmed Story Creative Memory',
            memory:
                $storyMemory,
        );

        $this->appendMemorySection(
            sections: $sections,
            heading:
                'Confirmed Emotion Creative Memory',
            memory:
                $emotionMemory,
        );

        $this->appendMemorySection(
            sections: $sections,
            heading:
                'Confirmed Identity Creative Memory',
            memory:
                $identityMemory,
        );

        $this->appendMemorySection(
            sections: $sections,
            heading:
                'Confirmed Sound Creative Memory',
            memory:
                $soundMemory,
        );

        $this->appendMemorySection(
            sections: $sections,
            heading:
                'Confirmed Impact Creative Memory',
            memory:
                $impactMemory,
        );

        $sections[] =
            '# Generation Instruction';

        $sections[] = '';

        $sections[] = (
            'Create the complete Song Style Addendum for this specific song. '
            . 'Explain how the finished lyrics should be realized within '
            . 'the established Producer Style Guide.'
        );

        return trim(
            implode("\n", $sections)
        );
    }

    /**
     * @param list<string> $sections
     */
    private function appendMemorySection(
        array &$sections,
        string $heading,
        PillarMemory $memory,
    ): void {
        $sections[] =
            '# ' . $heading;

        $sections[] = '';

        $sections[] =
            $this->encodeDocument(
                $memory->data()
            );

        $sections[] = '';
    }

    /**
     * @param array<string, mixed> $document
     */
    private function encodeDocument(
        array $document,
    ): string {
        return json_encode(
            $document,
            JSON_THROW_ON_ERROR
            | JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        return [
            'type' => 'object',

            'properties' => [
                'title' => [
                    'type' => 'string',
                ],

                'content' => [
                    'type' => 'string',
                ],
            ],

            'required' => [
                'title',
                'content',
            ],

            'additionalProperties' =>
                false,
        ];
    }

    private function requiredString(
        mixed $value,
        string $errorMessage,
    ): string {
        if (!is_string($value)) {
            throw new \RuntimeException(
                $errorMessage
            );
        }

        $value = trim($value);

        if ($value === '') {
            throw new \RuntimeException(
                $errorMessage
            );
        }

        return $value;
    }
}