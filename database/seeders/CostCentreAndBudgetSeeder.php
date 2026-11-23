<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Company;
use App\Models\CostCentre;
use Illuminate\Database\Seeder;

class CostCentreAndBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $mum = Company::where('code', 'ACME-MUM')->firstOrFail();
        $blr = Company::where('code', 'ACME-BLR')->firstOrFail();

        $fy = '2026-27';

        $data = [
            'ACME-MUM' => [
                'company' => $mum,
                'items' => [
                    ['code' => 'PROD', 'name' => 'Production Mumbai', 'amount' => 5000000.00],
                    ['code' => 'IT', 'name' => 'Information Technology Mumbai', 'amount' => 1000000.00],
                    ['code' => 'ADMIN', 'name' => 'Administration Mumbai', 'amount' => 200000.00],
                    ['code' => 'SALES', 'name' => 'Sales & Marketing Mumbai', 'amount' => 100000.00],
                ],
            ],
            'ACME-BLR' => [
                'company' => $blr,
                'items' => [
                    ['code' => 'PROD', 'name' => 'Production Bengaluru', 'amount' => 3000000.00],
                    ['code' => 'IT', 'name' => 'Information Technology Bengaluru', 'amount' => 500000.00],
                ],
            ],
        ];

        foreach ($data as $group) {
            $company = $group['company'];
            foreach ($group['items'] as $item) {
                $cc = CostCentre::withoutGlobalScopes()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $item['code'],
                    ],
                    [
                        'name' => $item['name'],
                    ]
                );

                Budget::withoutGlobalScopes()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'cost_centre_id' => $cc->id,
                        'financial_year' => $fy,
                    ],
                    [
                        'amount' => $item['amount'],
                        'committed' => 0.00,
                        'spent' => 0.00,
                    ]
                );
            }
        }
    }
}
