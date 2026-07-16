<?php
declare(strict_types=1);

namespace SonicFoundry\Work;

enum WorkType: string
{
    case Single = 'single';
    case Ep = 'ep';
    case Album = 'album';
    case Soundtrack = 'soundtrack';
    case Score = 'score';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single',
            self::Ep => 'EP',
            self::Album => 'Album',
            self::Soundtrack => 'Soundtrack',
            self::Score => 'Score',
            self::Other => 'Other',
        };
    }
}