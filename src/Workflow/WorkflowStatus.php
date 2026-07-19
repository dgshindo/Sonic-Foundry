<?php
declare(strict_types=1);

namespace SonicFoundry\Workflow;

enum WorkflowStatus: string
{
    case Locked = 'locked';
    case Available = 'available';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Locked => 'Locked',
            self::Available => 'Available',
            self::Completed => 'Completed',
        };
    }

    public function isLocked(): bool
    {
        return $this === self::Locked;
    }

    public function isAvailable(): bool
    {
        return $this === self::Available;
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }
}