<?php
declare(strict_types=1);

namespace SonicFoundry\Artifact;

enum CreativeArtifactType: string
{
    case StyleGuide =
        'style_guide';

    case SongStyleAddendum =
        'song_style_addendum';

    case Lyrics =
        'lyrics';

    case MusicStyleGenerationPrompt =
        'music_style_generation_prompt';

    public function label(): string
    {
        return match ($this) {
            self::StyleGuide =>
                'Style Guide',

            self::SongStyleAddendum =>
                'Song Style Addendum',

            self::Lyrics =>
                'Lyrics',
        };
    }
}