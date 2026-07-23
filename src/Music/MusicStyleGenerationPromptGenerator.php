<?php
declare(strict_types=1);

namespace SonicFoundry\Music;

use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\Artifact\CreativeArtifact;
use SonicFoundry\Artifact\CreativeArtifactService;
use SonicFoundry\Artifact\CreativeArtifactType;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Work\WorkService;

final class MusicStyleGenerationPromptGenerator
{
    private const MAX_CHARACTERS = 1000;

    public function __construct(
        private OpenAIClient $openAI,
        private PromptAssembler $prompts,
        private CreativeArtifactService $artifacts,
        private WorkService $works,
    ) {
    }

    /**
     * @return array{
     *     title: string,
     *     content: string,
     *     characterCount: int
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
            type:
                CreativeArtifactType::StyleGuide,
            missingMessage:
                'A Producer Style Guide is required before generating a Music Style Generation Prompt.',
        );

        $songStyle = $this->requiredArtifact(
            user: $user,
            workId: $work->id(),
            type:
                CreativeArtifactType::SongStyleAddendum,
            missingMessage:
                'A Song Style Addendum is required before generating a Music Style Generation Prompt.',
        );

        $instructions = $this->prompts
            ->assemble(
                promptPaths: [
                    'music/music-style-generation-prompt.md',
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
                instructions:
                    $instructions,

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

                                songStyle:
                                    $songStyle,
                            ),
                    ],
                ],

                schemaName:
                    'music_style_generation_prompt',

                schema:
                    $this->responseSchema(),
            );

        $prompt = $this->requiredString(
            value:
                $result['prompt']
                ?? null,

            errorMessage:
                'The generated Music Style Generation Prompt was empty.',
        );

        $prompt = preg_replace(
            '/\s+/u',
            ' ',
            $prompt
        );

        if (!is_string($prompt)) {
            throw new \RuntimeException(
                'The Music Style Generation Prompt could not be normalized.'
            );
        }

        $prompt = trim($prompt);

        $characterCount = mb_strlen(
            $prompt,
            'UTF-8'
        );

        if ($characterCount > self::MAX_CHARACTERS) {
            throw new \RuntimeException(
                sprintf(
                    'The Music Style Generation Prompt contains %d characters; the maximum is %d.',
                    $characterCount,
                    self::MAX_CHARACTERS
                )
            );
        }

        $characterCount =
            mb_strlen(
                $prompt,
                'UTF-8'
            );

        if (
            $characterCount
            > self::MAX_CHARACTERS
        ) {
            throw new \RuntimeException(
                sprintf(
                    'The Music Style Generation Prompt exceeded the 1000-character limit by %d characters.',
                    $characterCount
                        - self::MAX_CHARACTERS
                )
            );
        }

        return [
            'title' =>
                'Music Style Generation Prompt',

            'content' =>
                $prompt,

            'characterCount' =>
                $characterCount,
        ];
    }

    /* Generate and Save */
    public function generateAndSave(
        AuthenticatedUser $user,
        int $workId,
    ): CreativeArtifact {
        $generated = $this->generator
            ->generate(
                user: $user,
                workId: $workId,
            );

        $content = trim(
            $generated['content']
        );

        $characterCount = mb_strlen(
            $content,
            'UTF-8'
        );

        if ($characterCount > 1000) {
            throw new \RuntimeException(
                sprintf(
                    'Refusing to save a Music Style Generation Prompt containing %d characters.',
                    $characterCount
                )
            );
        }

        return $this->artifacts
            ->save(
                user: $user,
                workId: $workId,
                type:
                    CreativeArtifactType::MusicStyleGenerationPrompt,
                title:
                    $generated['title'],
                content:
                    $content,
            );
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
        CreativeArtifact $songStyle,
    ): string {
        return trim(
            implode(
                "\n",
                [
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
                    '# Approved Producer Style Guide',
                    '',
                    '## ' . $styleGuide->title(),
                    '',
                    $styleGuide->content(),
                    '',
                    '# Approved Song Style Addendum',
                    '',
                    '## ' . $songStyle->title(),
                    '',
                    $songStyle->content(),
                    '',
                    '# Generation Instruction',
                    '',
                    (
                        'Compress the approved production direction '
                        . 'into one plain-text Music Style Generation '
                        . 'Prompt containing no more than 1000 '
                        . 'characters, including spaces and punctuation.'
                    ),
                ]
            )
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
                'prompt' => [
                    'type' => 'string',
                    'maxLength' =>
                        self::MAX_CHARACTERS,
                ],
            ],

            'required' => [
                'prompt',
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