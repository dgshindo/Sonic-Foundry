<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Contracts;

final class ConversationDefinition
{
    public function __construct(
        private string $promptPath,
    ) {
        $this->assertValidPromptPath(
            $this->promptPath
        );
    }

    public function promptPath(): string
    {
        return $this->promptPath;
    }

    private function assertValidPromptPath(
        string $promptPath,
    ): void {
        $promptPath = trim(
            $promptPath
        );

        if ($promptPath === '') {
            throw new \InvalidArgumentException(
                'Conversation prompt path cannot be empty.'
            );
        }

        if (!str_ends_with($promptPath, '.md')) {
            throw new \InvalidArgumentException(
                'Conversation prompt must reference a Markdown file.'
            );
        }

        if (
            str_starts_with($promptPath, '/')
            || str_contains($promptPath, '..')
        ) {
            throw new \InvalidArgumentException(
                'Conversation prompt path must be relative and safe.'
            );
        }
    }
}