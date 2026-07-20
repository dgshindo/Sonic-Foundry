<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Contracts;

use SonicFoundry\Pillars\Support\MemoryFieldDefinition;

final class MemoryDefinition
{
    /**
     * @param list<MemoryFieldDefinition> $fields
     */
    public function __construct(
        private array $fields,
    ) {
        foreach ($this->fields as $field) {
            if (!$field instanceof MemoryFieldDefinition) {
                throw new \InvalidArgumentException(
                    'Every memory field must be a MemoryFieldDefinition.'
                );
            }
        }
    }

    /**
     * @return list<MemoryFieldDefinition>
     */
    public function fields(): array
    {
        return $this->fields;
    }
}