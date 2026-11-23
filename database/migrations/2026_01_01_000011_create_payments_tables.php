<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_mode', 50)->default('NEFT'); // NEFT, RTGS, CHEQUE
            $table->string('reference_number', 100)->nullable();
            $table->string('status', 30)->default('COMPLETED');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'payment_date']);
            $table->index('vendor_id');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('vendor_invoice_id')->constrained('vendor_invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('tds_amount', 15, 2)->default(0.00);
            $table->timestamps();

            $table->index(['payment_id', 'vendor_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
