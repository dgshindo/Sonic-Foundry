<?php
declare(strict_types=1);

namespace SonicFoundry\AI;

final class PromptAssembler
{
    public function __construct(
        private readonly PromptLoader $loader,
    ) {
    }

    /**
     * @param list<string> $promptPaths
     * @param array<string, scalar|null> $variables
     */
    public function assemble(
        array $promptPaths,
        array $variables = [],
    ): string {
        if ($promptPaths === []) {
            throw new \InvalidArgumentException(
                'At least one prompt file is required.'
            );
        }

        $sections = [];

        foreach ($promptPaths as $promptPath) {
            $prompt = $this->loader->load(
                $promptPath
            );

            $sections[] = $prompt->content();
        }

        $assembled = implode(
            "\n\n---\n\n",
            $sections
        );

        return $this->replaceVariables(
            $assembled,
            $variables
        );
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    private function replaceVariables(
        string $content,
        array $variables,
    ): string {
        $replacements = [];

        foreach ($variables as $name => $value) {
            $replacements[
                '{{' . $name . '}}'
            ] = $value === null
                ? ''
                : (string) $value;
        }

        $rendered = strtr(
            $content,
            $replacements
        );

        if (
            preg_match(
                '/\{\{[A-Za-z0-9_.-]+\}\}/',
                $rendered,
                $matches
            ) === 1
        ) {
            throw new \RuntimeException(
                'Prompt variable was not supplied: '
                . $matches[0]
            );
        }

        return trim($rendered);
    }
}