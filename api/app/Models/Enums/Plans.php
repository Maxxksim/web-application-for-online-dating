<?php

namespace App\Models\Enums;

use App\Models\Traits\EnumToArrayTrait;

enum Plans: string
{
    use EnumToArrayTrait;

    case PREMIUM = 'premium';

}
