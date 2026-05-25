<?php

namespace App\Models\Enums;

use App\Models\Traits\EnumToArrayTrait;

enum ZodiacSign: string
{
    use EnumToArrayTrait;

    case ARIES = 'aries';
    case TAURUS = 'taurus';
    case GEMINI = 'gemini';
    case CANCER = 'cancer';
    case LEO = 'leo';
    case VIRGO = 'virgo';
    case LIBRA = 'libra';
    case SCORPIO = 'scorpio';
    case SAGITTARIUS = 'sagittarius';
    case CAPRICORN = 'capricorn';
    case AQUARIUS = 'aquarius';
    case PISCES = 'pisces';

}
