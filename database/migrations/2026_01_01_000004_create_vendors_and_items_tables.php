<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('state', 100);
            $table->string('gstin', 15)->nullable();
            $table->integer('payment_terms_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('category', 50); // RAW_MATERIAL, IT, SERVICES, CONSUMABLES
            $table->string('base_uom_code', 50);
            $table->decimal('gst_rate', 5, 2); // e.g. 18.00
            $table->timestamps();

            $table->foreign('base_uom_code')->references('code')->on('units_of_measure');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
        Schema::dropIfExists('vendors');
    }
};
