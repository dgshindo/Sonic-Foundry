<?php
declare(strict_types=1);

namespace SonicFoundry\Work;

enum WorkPillar: string
{
    case Story = 'story';
    case Emotion = 'emotion';
    case Identity = 'identity';
    case Sound = 'sound';
    case Impact = 'impact';

    public function label(): string
    {
        return match ($this) {
            self::Story => 'Story',
            self::Emotion => 'Emotion',
            self::Identity => 'Identity',
            self::Sound => 'Sound',
            self::Impact => 'Impact',
        };
    }
}