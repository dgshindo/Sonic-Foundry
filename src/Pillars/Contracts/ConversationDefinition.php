<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Contracts;

final class ConversationDefinition
{
    public function __construct(
        private string $prompt,
    ) {
    }

    public function prompt(): string
    {
        return $this->prompt;
    }
}