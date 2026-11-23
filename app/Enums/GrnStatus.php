<?php

namespace App\Enums;

enum GrnStatus: string
{
    case DRAFT = 'DRAFT';
    case RECEIVED = 'RECEIVED';
    case INSPECTED = 'INSPECTED';
    case CANCELLED = 'CANCELLED';
}
