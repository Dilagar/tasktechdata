<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('invoice_number', 100);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('DRAFT'); // InvoiceStatus
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_total', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'invoice_number']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('vendor_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_invoice_id')->constrained('vendor_invoices')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();
            $table->foreignId('goods_receipt_line_id')->nullable()->constrained('goods_receipt_lines')->nullOnDelete();
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
            $table->string('match_status', 30)->default('PENDING'); // PENDING, PASSED, FAILED
            $table->text('hold_reason')->nullable();
            $table->timestamps();

            $table->foreign('uom_code')->references('code')->on('units_of_measure');
            $table->unique(['vendor_invoice_id', 'line_number'], 'inv_lines_inv_id_line_no_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_invoice_lines');
        Schema::dropIfExists('vendor_invoices');
    }
};
