<?php

namespace App\Models\Enums;

use App\Models\Traits\EnumToArrayTrait;

enum HairColor: string
{
    use EnumToArrayTrait;

    case BLACK = 'black';
    case BROWN = 'brown';
    case BLONDE = 'blonde';
    case RED = 'red';
    case GRAY = 'gray';
    case WHITE = 'white';
    case OTHER = 'other';

}
