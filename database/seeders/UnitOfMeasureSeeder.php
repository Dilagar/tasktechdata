<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'PCS', 'base_unit_code' => 'PCS', 'factor' => 1.000000],
            ['code' => 'BOX', 'base_unit_code' => 'PCS', 'factor' => 12.000000],
            ['code' => 'KG',  'base_unit_code' => 'KG',  'factor' => 1.000000],
            ['code' => 'TON', 'base_unit_code' => 'KG',  'factor' => 1000.000000],
            ['code' => 'HRS', 'base_unit_code' => 'HRS', 'factor' => 1.000000],
        ];

        foreach ($units as $uom) {
            UnitOfMeasure::updateOrCreate(['code' => $uom['code']], $uom);
        }
    }
}
