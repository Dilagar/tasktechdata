<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case MATCH_PASSED = 'MATCH_PASSED';
    case ON_HOLD = 'ON_HOLD';
    case APPROVED = 'APPROVED';
    case PARTIALLY_PAID = 'PARTIALLY_PAID';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';
}
