<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            // unit_price_base = unit_price / uom factor (DECIMAL 15,4) for price matching across BOX/PCS
            $table->decimal('unit_price_base', 15, 4)->default(0.0000)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn('unit_price_base');
        });
    }
};
