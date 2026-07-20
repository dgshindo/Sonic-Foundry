<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Support;

enum MemoryFieldType: string
{
    case Text = 'text';

    case List = 'list';
}