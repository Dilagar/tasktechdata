<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Procure-to-Pay (P2P) Global Tolerances & Configuration Parameters
    |--------------------------------------------------------------------------
    | All percentage tolerances and rates are maintained centrally here.
    | Code must read from config('p2p....') and never use hardcoded magic numbers.
    */

    // Maximum percentage by which accepted GRN quantity may exceed PO ordered quantity
    'over_receipt_tolerance_percent' => 5,

    // Maximum percentage variance allowed in 3-way matching between invoice and PO unit price
    'price_tolerance_percent' => 2,

    // Tax Deducted at Source (TDS) deduction percentage for service categories
    'tds_rate_percent' => 2,

    // Categories subject to TDS deduction
    'tds_categories' => [
        'SERVICES',
    ],

    // Month index starting the Financial Year (4 = April, March-April cycle: e.g. 2026-27)
    'fy_start_month' => 4,
];
