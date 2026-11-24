<?php

namespace App\Services;

use App\Models\Item;
use App\Models\UnitOfMeasure;
use InvalidArgumentException;

class UomConverter
{
    /**
     * Convert an entered quantity in a given UoM code to the item's base quantity.
     * Enforces that the UoM shares the same base unit family as the item.
     *
     * @param string|int|float $quantity Entered quantity (e.g. 5)
     * @param string $uomCode Entered UoM code (e.g. 'BOX')
     * @param Item $item Item model containing base_uom_code
     * @return string Base quantity formatted with 3 decimal places
     * @throws InvalidArgumentException
     */
    public static function toBase(string|int|float $quantity, string $uomCode, Item $item): string
    {
        $uom = UnitOfMeasure::where('code', $uomCode)->first();
        if (! $uom) {
            throw new InvalidArgumentException("Unit of Measure '{$uomCode}' not found.");
        }

        // Validate unit family: uom base_unit_code must match the item's base_uom_code
        if ($uom->base_unit_code !== $item->base_uom_code) {
            throw new InvalidArgumentException(
                "Invalid Unit of Measure '{$uomCode}' for item '{$item->name}'. " .
                "The unit belongs to base unit '{$uom->base_unit_code}', but the item requires '{$item->base_uom_code}'."
            );
        }

        $qtyStr = number_format((float) $quantity, 3, '.', '');
        $factorStr = (string) $uom->factor;

        // base_quantity = entered_quantity * factor
        return bcmul($qtyStr, $factorStr, 3);
    }
}
