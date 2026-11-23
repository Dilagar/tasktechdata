<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $mum = Company::where('code', 'ACME-MUM')->firstOrFail();
        $blr = Company::where('code', 'ACME-BLR')->firstOrFail();

        $vendors = [
            [
                'code' => 'V001',
                'name' => 'Shree Steel Traders',
                'state' => 'Maharashtra',
                'gstin' => '27AABCS1111A1Z1',
                'payment_terms_days' => 30,
                'company' => $mum,
            ],
            [
                'code' => 'V002',
                'name' => 'Bangalore Tech Supplies',
                'state' => 'Karnataka',
                'gstin' => '29AABCB2222B1Z2',
                'payment_terms_days' => 45,
                'company' => $mum,
            ],
            [
                'code' => 'V003',
                'name' => 'QuickClean Services',
                'state' => 'Maharashtra',
                'gstin' => '27AABCQ3333C1Z3',
                'payment_terms_days' => 15,
                'company' => $mum,
            ],
            [
                'code' => 'V004',
                'name' => 'Deccan Packaging',
                'state' => 'Karnataka',
                'gstin' => '29AABCD4444D1Z4',
                'payment_terms_days' => 30,
                'company' => $blr,
            ],
        ];

        foreach ($vendors as $v) {
            Vendor::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $v['company']->id,
                    'code' => $v['code'],
                ],
                [
                    'name' => $v['name'],
                    'state' => $v['state'],
                    'gstin' => $v['gstin'],
                    'payment_terms_days' => $v['payment_terms_days'],
                    'is_active' => true,
                ]
            );
        }
    }
}
