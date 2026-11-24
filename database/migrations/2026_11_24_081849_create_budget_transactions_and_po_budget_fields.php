<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add committed_amount and po_date to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('po_date')->nullable()->after('po_number');
            $table->decimal('committed_amount', 15, 2)->default(0.00)->after('grand_total');
        });

        // Create append-only budget_transactions table
        Schema::create('budget_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('type', 20); // COMMIT, RELEASE, SPEND
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after_committed', 15, 2);
            $table->decimal('balance_after_spent', 15, 2);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['budget_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_transactions');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['po_date', 'committed_amount']);
        });
    }
};
