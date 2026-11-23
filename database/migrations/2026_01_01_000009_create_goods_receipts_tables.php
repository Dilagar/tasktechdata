<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('RECEIVED'); // GrnStatus
            $table->date('receipt_date');
            $table->string('delivery_challan_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['purchase_order_id', 'receipt_date']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained('purchase_order_lines')->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('uom_code', 50);
            $table->decimal('received_qty', 15, 3);
            $table->decimal('accepted_qty', 15, 3);
            $table->decimal('rejected_qty', 15, 3)->default(0.000);
            $table->text('rejection_reason')->nullable();
            $table->decimal('base_received_qty', 15, 3);
            $table->decimal('base_accepted_qty', 15, 3);
            $table->decimal('base_rejected_qty', 15, 3)->default(0.000);
            $table->timestamps();

            $table->foreign('uom_code')->references('code')->on('units_of_measure');
            $table->unique(['goods_receipt_id', 'line_number'], 'grn_lines_grn_id_line_no_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
    }
};
