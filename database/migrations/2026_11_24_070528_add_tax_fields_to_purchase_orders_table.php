<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('cost_centre_id')->nullable()->after('vendor_id')->constrained('cost_centres')->nullOnDelete();
            $table->decimal('total_cgst', 15, 2)->default(0.00)->after('subtotal');
            $table->decimal('total_sgst', 15, 2)->default(0.00)->after('total_cgst');
            $table->decimal('total_igst', 15, 2)->default(0.00)->after('total_sgst');
            $table->decimal('grand_total', 15, 2)->default(0.00)->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['cost_centre_id']);
            $table->dropColumn([
                'cost_centre_id',
                'total_cgst',
                'total_sgst',
                'total_igst',
                'grand_total',
            ]);
        });
    }
};
