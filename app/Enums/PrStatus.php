<?php

namespace App\Enums;

enum PrStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case PO_CREATED = 'PO_CREATED';
    case CANCELLED = 'CANCELLED';
}
