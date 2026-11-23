<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('pr_number', 50)->unique();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cost_centre_id')->constrained('cost_centres')->cascadeOnDelete();
            $table->string('status', 30)->default('DRAFT'); // PrStatus
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['cost_centre_id', 'created_at']);
        });

        Schema::create('purchase_requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('uom_code', 50);
            $table->decimal('base_quantity', 15, 3);
            $table->decimal('estimated_unit_price', 15, 2)->default(0.00);
            $table->decimal('estimated_total', 15, 2)->default(0.00);
            $table->decimal('ordered_quantity', 15, 3)->default(0.000);
            $table->timestamps();

            $table->foreign('uom_code')->references('code')->on('units_of_measure');
            $table->unique(['purchase_requisition_id', 'line_number'], 'pr_lines_pr_id_line_no_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_lines');
        Schema::dropIfExists('purchase_requisitions');
    }
};
