<?php
declare(strict_types=1);

namespace SonicFoundry\AI;

final class PromptLoader
{
    public function __construct(
        private readonly string $promptRoot,
    ) {
        if (!is_dir($this->promptRoot)) {
            throw new \RuntimeException(
                'Prompt directory does not exist: '
                . $this->promptRoot
            );
        }
    }

    public function load(
        string $relativePath,
    ): Prompt {
        $normalizedPath = $this->normalizeRelativePath(
            $relativePath
        );

        $fullPath = $this->promptRoot
            . DIRECTORY_SEPARATOR
            . str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $normalizedPath
            );

        $resolvedRoot = realpath(
            $this->promptRoot
        );

        $resolvedFile = realpath(
            $fullPath
        );

        if (
            $resolvedRoot === false
            || $resolvedFile === false
            || !str_starts_with(
                $resolvedFile,
                $resolvedRoot
                    . DIRECTORY_SEPARATOR
            )
        ) {
            throw new \RuntimeException(
                'Prompt file could not be found: '
                . $relativePath
            );
        }

        if (!is_file($resolvedFile)) {
            throw new \RuntimeException(
                'Prompt path is not a file: '
                . $relativePath
            );
        }

        $rawContent = file_get_contents(
            $resolvedFile
        );

        if ($rawContent === false) {
            throw new \RuntimeException(
                'Prompt file could not be read: '
                . $relativePath
            );
        }

        [
            $metadata,
            $content
        ] = $this->parseDocument(
            $rawContent
        );

        $name = $metadata['name']
            ?? pathinfo(
                $resolvedFile,
                PATHINFO_FILENAME
            );

        return new Prompt(
            name: $name,
            content: trim($content),
            metadata: $metadata,
        );
    }

    private function normalizeRelativePath(
        string $relativePath,
    ): string {
        $normalized = str_replace(
            '\\',
            '/',
            trim($relativePath)
        );

        $normalized = ltrim(
            $normalized,
            '/'
        );

        if (
            $normalized === ''
            || str_contains(
                $normalized,
                '..'
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid prompt path.'
            );
        }

        if (!str_ends_with($normalized, '.md')) {
            throw new \InvalidArgumentException(
                'Prompt files must use the .md extension.'
            );
        }

        return $normalized;
    }

    /**
     * @return array{
     *     0: array<string, string>,
     *     1: string
     * }
     */
    private function parseDocument(
        string $rawContent,
    ): array {
        $normalized = str_replace(
            [
                "\r\n",
                "\r"
            ],
            "\n",
            $rawContent
        );

        if (!str_starts_with($normalized, "---\n")) {
            return [
                [],
                $normalized,
            ];
        }

        $frontMatterEnd = strpos(
            $normalized,
            "\n---\n",
            4
        );

        if ($frontMatterEnd === false) {
            throw new \RuntimeException(
                'Prompt front matter is not properly closed.'
            );
        }

        $frontMatter = substr(
            $normalized,
            4,
            $frontMatterEnd - 4
        );

        $content = substr(
            $normalized,
            $frontMatterEnd + 5
        );

        $metadata = [];

        foreach (
            explode(
                "\n",
                $frontMatter
            )
            as $line
        ) {
            $line = trim($line);

            if (
                $line === ''
                || str_starts_with(
                    $line,
                    '#'
                )
            ) {
                continue;
            }

            [
                $key,
                $value
            ] = array_pad(
                explode(
                    ':',
                    $line,
                    2
                ),
                2,
                ''
            );

            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            $metadata[$key] = trim(
                $value,
                " \t\n\r\0\x0B\"'"
            );
        }

        return [
            $metadata,
            $content,
        ];
    }
}