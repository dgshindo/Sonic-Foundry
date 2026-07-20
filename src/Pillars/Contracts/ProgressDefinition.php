<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Contracts;

use SonicFoundry\Pillars\Support\ProgressCriterion;

final class ProgressDefinition
{
    /**
     * @param list<ProgressCriterion> $criteria
     */
    public function __construct(
        private string $title,
        private string $promptPath,
        private array $criteria,
        private int $completionThreshold = 80,
    ) {
        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Progress title cannot be empty.'
            );
        }

        $this->assertValidPromptPath(
            $this->promptPath
        );

        if ($this->criteria === []) {
            throw new \InvalidArgumentException(
                'A progress definition requires criteria.'
            );
        }

        $knownKeys = [];

        foreach ($this->criteria as $criterion) {
            if (
                !$criterion
                instanceof ProgressCriterion
            ) {
                throw new \InvalidArgumentException(
                    'Every progress criterion must be a ProgressCriterion.'
                );
            }

            $key = $criterion->key();

            if (isset($knownKeys[$key])) {
                throw new \InvalidArgumentException(
                    'Progress criterion keys must be unique.'
                );
            }

            $knownKeys[$key] = true;
        }

        if (
            $this->completionThreshold < 0
            || $this->completionThreshold > 100
        ) {
            throw new \InvalidArgumentException(
                'Progress completion threshold must be between 0 and 100.'
            );
        }
    }

    public function title(): string
    {
        return $this->title;
    }

    public function promptPath(): string
    {
        return $this->promptPath;
    }

    /**
     * @return list<ProgressCriterion>
     */
    public function criteria(): array
    {
        return $this->criteria;
    }

    public function completionThreshold(): int
    {
        return $this->completionThreshold;
    }

    private function assertValidPromptPath(
        string $promptPath,
    ): void {
        $promptPath = trim(
            $promptPath
        );

        if ($promptPath === '') {
            throw new \InvalidArgumentException(
                'Progress prompt path cannot be empty.'
            );
        }

        if (!str_ends_with($promptPath, '.md')) {
            throw new \InvalidArgumentException(
                'Progress prompt must reference a Markdown file.'
            );
        }

        if (
            str_starts_with($promptPath, '/')
            || str_contains($promptPath, '..')
        ) {
            throw new \InvalidArgumentException(
                'Progress prompt path must be relative and safe.'
            );
        }
    }
}