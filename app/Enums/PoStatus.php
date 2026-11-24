<?php

namespace App\Enums;

enum PoStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING_APPROVAL = 'PENDING_APPROVAL';
    case APPROVED = 'APPROVED';
    case PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    case RECEIVED = 'RECEIVED';
    case CLOSED = 'CLOSED';
    case CANCELLED = 'CANCELLED';
}
