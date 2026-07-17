<?php
declare(strict_types=1);

namespace SonicFoundry\Conversation;

enum MessageRole: string
{
    case User = 'user';
    case Partner = 'partner';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Creator',
            self::Partner => 'Creative Partner',
            self::System => 'System',
        };
    }
}