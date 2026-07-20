<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Support;

enum ProgressLevel: string
{
    case NotStarted = 'not_started';

    case InProgress = 'in_progress';

    case Ready = 'ready';

    case Complete = 'complete';
}