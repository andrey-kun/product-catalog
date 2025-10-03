<?php

declare(strict_types=1);

namespace App\Contract\DaData;

enum PartyType: string
{
    case LEGAL = 'LEGAL';
    case INDIVIDUAL = 'INDIVIDUAL';
}
