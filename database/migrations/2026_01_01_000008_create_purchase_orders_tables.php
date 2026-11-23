<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('po_number', 50)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('DRAFT'); // PoStatus
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_total', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->integer('payment_terms_days')->default(30);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['vendor_id', 'created_at']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('uom_code', 50);
            $table->decimal('base_quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('taxable_value', 15, 2);
            $table->decimal('gst_rate', 5, 2)->default(0.00);
            $table->decimal('cgst', 15, 2)->default(0.00);
            $table->decimal('sgst', 15, 2)->default(0.00);
            $table->decimal('igst', 15, 2)->default(0.00);
            $table->decimal('line_total', 15, 2);
            $table->decimal('received_quantity', 15, 3)->default(0.000);
            $table->decimal('billed_quantity', 15, 3)->default(0.000);
            $table->timestamps();

            $table->foreign('uom_code')->references('code')->on('units_of_measure');
            $table->unique(['purchase_order_id', 'line_number'], 'po_lines_po_id_line_no_unique');
        });

        Schema::create('po_pr_line', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_line_id')->constrained('purchase_order_lines')->cascadeOnDelete();
            $table->foreignId('purchase_requisition_line_id')->constrained('purchase_requisition_lines')->cascadeOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->timestamps();

            $table->index(['purchase_order_line_id', 'purchase_requisition_line_id'], 'idx_po_pr_line');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_pr_line');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
