<?php

namespace App\Models\Enums;

use App\Models\Traits\EnumToArrayTrait;

enum BodyType: string
{
    use EnumToArrayTrait;

    case SLIM = 'slim';
    case ATHLETIC = 'athletic';
    case AVERAGE = 'average';
    case CURVY = 'curvy';
    case PLUS_SIZE = 'plus_size';

}
