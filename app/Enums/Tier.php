<?php

declare(strict_types=1);

namespace App\Enums;

enum Tier: string
{
    case UNDEFINED = '';
    case FT_PLUS = 'FT+';
    case FT1 = 'FT1';
    case FT2 = 'FT2';
    case FT3 = 'FT3';
    case FT4 = 'FT4';
}
