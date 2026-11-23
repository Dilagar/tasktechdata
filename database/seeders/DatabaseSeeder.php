<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            RoleAndUserSeeder::class,
            UnitOfMeasureSeeder::class,
            CostCentreAndBudgetSeeder::class,
            VendorSeeder::class,
            ItemSeeder::class,
            ApprovalRuleSeeder::class,
        ]);
    }
}
