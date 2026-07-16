<?php
declare(strict_types=1);

namespace SonicFoundry\Work;

enum WorkStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Complete = 'complete';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'In Progress',
            self::Complete => 'Complete',
            self::Archived => 'Archived',
        };
    }
}