<?php
declare(strict_types=1);

namespace SonicFoundry\Style;

use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Memory\PillarMemory;
use SonicFoundry\Memory\PillarMemoryService;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class StyleGuideGenerator
{
    public function __construct(
        private OpenAIClient $openAI,
        private PromptAssembler $prompts,
        private PillarMemoryService $memory,
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
                    'style/album-style-guide.md',
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
                    'style_guide_generation',

                schema:
                    $this->responseSchema(),
            );

        $title = $this->requiredString(
            value:
                $result['title']
                ?? null,

            errorMessage:
                'The generated Style Guide did not include a title.',
        );

        $content = $this->requiredString(
            value:
                $result['content']
                ?? null,

            errorMessage:
                'The generated Style Guide did not include content.',
        );

        return [
            'title' => $title,
            'content' => $content,
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
                pillarValue:
                    $pillar->value,
            );

        if ($memory === null) {
            throw new \DomainException(
                sprintf(
                    'A confirmed %s Creative Memory is required before generating a Style Guide.',
                    $pillar->label()
                )
            );
        }

        if (!$memory->isConfirmed()) {
            throw new \DomainException(
                sprintf(
                    '%s Creative Memory must be confirmed before generating a Style Guide.',
                    $pillar->label()
                )
            );
        }

        return $memory;
    }

    private function buildInputDocument(
        string $workTitle,
        string $workType,
        PillarMemory $storyMemory,
        PillarMemory $emotionMemory,
        PillarMemory $identityMemory,
        PillarMemory $soundMemory,
        PillarMemory $impactMemory,
    ): string {
        $sections = [
            '# Work',
            '',
            '## Title',
            '',
            $workTitle,
            '',
            '## Type',
            '',
            $workType,
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
            'Synthesize these confirmed creative foundations '
            . 'into the definitive Style Guide for this Work.'
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