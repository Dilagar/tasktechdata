<?php

namespace App\Enums;

enum PoStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case ISSUED = 'ISSUED';
    case PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    case FULLY_RECEIVED = 'FULLY_RECEIVED';
    case CLOSED = 'CLOSED';
    case CANCELLED = 'CANCELLED';
}
