<?php
declare(strict_types=1);

namespace SonicFoundry\Progress;

enum ProgressStatus: string
{
    case Developing = 'developing';
    case Ready = 'ready';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Developing => 'Developing',
            self::Ready => 'Ready for Review',
            self::Completed => 'Completed',
        };
    }
}