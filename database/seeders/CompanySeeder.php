<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'code' => 'ACME-MUM',
                'name' => 'Acme Mumbai Pvt Ltd',
                'state' => 'Maharashtra',
                'gstin' => '27AAACA1234A1Z5',
            ],
            [
                'code' => 'ACME-BLR',
                'name' => 'Acme Bengaluru Pvt Ltd',
                'state' => 'Karnataka',
                'gstin' => '29AAACA1234A1Z1',
            ],
        ];

        foreach ($companies as $company) {
            Company::updateOrCreate(['code' => $company['code']], $company);
        }
    }
}
