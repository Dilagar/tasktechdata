<?php

namespace Database\Seeders;

use App\Enums\ItemCategory;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'code' => 'I001',
                'name' => 'MS Steel Sheet 2mm',
                'category' => ItemCategory::RAW_MATERIAL,
                'base_uom_code' => 'KG',
                'gst_rate' => 18.00,
            ],
            [
                'code' => 'I002',
                'name' => 'Laptop i5 16GB',
                'category' => ItemCategory::IT,
                'base_uom_code' => 'PCS',
                'gst_rate' => 18.00,
            ],
            [
                'code' => 'I003',
                'name' => 'A4 Paper Ream',
                'category' => ItemCategory::CONSUMABLES,
                'base_uom_code' => 'PCS',
                'gst_rate' => 12.00,
            ],
            [
                'code' => 'I004',
                'name' => 'Housekeeping Service',
                'category' => ItemCategory::SERVICES,
                'base_uom_code' => 'HRS',
                'gst_rate' => 18.00,
            ],
            [
                'code' => 'I005',
                'name' => 'Corrugated Box',
                'category' => ItemCategory::CONSUMABLES,
                'base_uom_code' => 'PCS',
                'gst_rate' => 12.00,
            ],
            [
                'code' => 'I006',
                'name' => 'Rice (canteen)',
                'category' => ItemCategory::CONSUMABLES,
                'base_uom_code' => 'KG',
                'gst_rate' => 0.00,
            ],
        ];

        foreach ($items as $item) {
            Item::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
