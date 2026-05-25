<?php
namespace App\Models\Enums;

use App\Models\Traits\EnumToArrayTrait;

enum Habit: string
{
    use EnumToArrayTrait;
    case NEVER = 'never';
    case SOMETIMES = 'sometimes';
    case OFTEN = 'often';
}
