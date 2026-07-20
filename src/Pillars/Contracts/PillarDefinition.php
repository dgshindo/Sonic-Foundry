<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Contracts;

use SonicFoundry\Work\WorkPillar;

interface PillarDefinition
{
    public function pillar(): WorkPillar;

    public function conversation(): ConversationDefinition;

    public function memory(): MemoryDefinition;

    public function progress(): ProgressDefinition;

    public function extractionPrompt(): string;
}