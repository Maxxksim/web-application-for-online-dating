<?php

namespace App\Models\Traits;

/**
 * @method static cases()
 */
trait EnumToArrayTrait
{
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
