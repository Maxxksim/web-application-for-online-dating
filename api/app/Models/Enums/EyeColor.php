<?php

namespace App\Models\Enums;

use App\Models\Traits\EnumToArrayTrait;

enum EyeColor: string
{
    use EnumToArrayTrait;

    case BROWN = 'brown';
    case DARK_BROWN = 'dark_brown';
    case BLUE = 'blue';
    case DARK_BLUE = 'dark_blue';
    case GREEN = 'green';
    case GRAY = 'gray';
    case GRAY_GREEN = 'gray_green';
    case GRAY_BLUE = 'gray_blue';
    case HAZEL = 'hazel';
    case AMBER = 'amber';
    case BLACK = 'black';
}

