<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

enum MemoryStatus: string
{
    case Draft = 'draft';
    case Proposed = 'proposed';
    case Confirmed = 'confirmed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Proposed => 'Awaiting Confirmation',
            self::Confirmed => 'Confirmed',
            self::Archived => 'Archived',
        };
    }
}