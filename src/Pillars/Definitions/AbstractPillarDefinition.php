<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Definitions;

use SonicFoundry\Pillars\Contracts\PillarDefinition;
use SonicFoundry\Pillars\Support\MemoryFieldDefinition;
use SonicFoundry\Pillars\Support\MemoryFieldType;
use SonicFoundry\Pillars\Support\ProgressCriterion;

abstract class AbstractPillarDefinition
    implements PillarDefinition
{
    protected function textField(
        string $key,
        string $label,
        string $emptyMessage,
    ): MemoryFieldDefinition {
        return new MemoryFieldDefinition(
            key: $key,
            label: $label,
            type: MemoryFieldType::Text,
            emptyMessage: $emptyMessage,
        );
    }

    protected function listField(
        string $key,
        string $label,
        string $emptyMessage,
    ): MemoryFieldDefinition {
        return new MemoryFieldDefinition(
            key: $key,
            label: $label,
            type: MemoryFieldType::List,
            emptyMessage: $emptyMessage,
        );
    }

    protected function criterion(
        string $key,
        string $label,
        string $description,
    ): ProgressCriterion {
        return new ProgressCriterion(
            key: $key,
            label: $label,
            description: $description,
        );
    }
}