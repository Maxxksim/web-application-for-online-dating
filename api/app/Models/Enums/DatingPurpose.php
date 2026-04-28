<?php

namespace App\Models\Enums;

use App\Models\Traits\EnumToArrayTrait;

enum DatingPurpose: string
{
    use EnumToArrayTrait;

    case SR = 'Serious Relationship';
    case LTP = 'Long-term Relationship';
    case STR = 'Short-term Relationship';
    case CF = 'Casual Friendship';
    case OTP = 'Open to Possibilities';
}
