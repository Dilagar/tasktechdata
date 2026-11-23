<?php

namespace App\Enums;

enum ItemCategory: string
{
    case RAW_MATERIAL = 'RAW_MATERIAL';
    case IT = 'IT';
    case SERVICES = 'SERVICES';
    case CONSUMABLES = 'CONSUMABLES';
}
