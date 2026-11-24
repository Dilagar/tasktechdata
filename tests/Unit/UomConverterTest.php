<?php

namespace Tests\Unit;

use App\Enums\ItemCategory;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Services\UomConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UomConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_converts_box_to_pcs_base_quantity(): void
    {
        UnitOfMeasure::create(['code' => 'PCS', 'base_unit_code' => 'PCS', 'factor' => 1.000000]);
        UnitOfMeasure::create(['code' => 'BOX', 'base_unit_code' => 'PCS', 'factor' => 12.000000]);

        $item = Item::create([
            'code' => 'TEST01',
            'name' => 'Widget',
            'category' => ItemCategory::CONSUMABLES,
            'base_uom_code' => 'PCS',
            'gst_rate' => 18.00,
        ]);

        $baseQty = UomConverter::toBase(5, 'BOX', $item);
        $this->assertSame('60.000', $baseQty);
    }

    public function test_rejects_incompatible_base_unit_family(): void
    {
        UnitOfMeasure::create(['code' => 'PCS', 'base_unit_code' => 'PCS', 'factor' => 1.000000]);
        UnitOfMeasure::create(['code' => 'KG', 'base_unit_code' => 'KG', 'factor' => 1.000000]);

        $item = Item::create([
            'code' => 'TEST02',
            'name' => 'Chair',
            'category' => ItemCategory::CONSUMABLES,
            'base_uom_code' => 'PCS',
            'gst_rate' => 18.00,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid Unit of Measure/i');

        UomConverter::toBase(2, 'KG', $item);
    }
}
