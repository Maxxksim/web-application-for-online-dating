<?php

namespace App\Models\Enums;

use App\Models\Traits\EnumToArrayTrait;

enum ChildrenStatus: string
{
    use EnumToArrayTrait;

    case HAS = 'has';
    case WANTS = 'wants';
    case DOESNT_WANT = 'doesnt_want';
    case OPEN = 'open';
}
