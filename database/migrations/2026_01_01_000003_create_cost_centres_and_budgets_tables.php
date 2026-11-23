<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cost_centre_id')->constrained('cost_centres')->cascadeOnDelete();
            $table->string('financial_year', 20); // e.g. "2026-27"
            $table->decimal('amount', 15, 2);
            $table->decimal('committed', 15, 2)->default(0.00);
            $table->decimal('spent', 15, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['cost_centre_id', 'financial_year']);
            $table->index(['company_id', 'financial_year']);
        });

        // Add CHECK constraint if supported (MySQL 8.0.16+ supports CHECK constraints)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE budgets ADD CONSTRAINT chk_budgets_amount CHECK (committed + spent <= amount)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('cost_centres');
    }
};
