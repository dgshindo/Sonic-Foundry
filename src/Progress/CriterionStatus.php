<?php
declare(strict_types=1);

namespace SonicFoundry\Progress;

enum CriterionStatus: string
{
    case Missing = 'missing';
    case Emerging = 'emerging';
    case Established = 'established';

    public function label(): string
    {
        return match ($this) {
            self::Missing => 'Missing',
            self::Emerging => 'Emerging',
            self::Established => 'Established',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Missing => 0,
            self::Emerging => 1,
            self::Established => 2,
        };
    }
}