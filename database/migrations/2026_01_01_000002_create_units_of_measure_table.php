<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('base_unit_code', 50);
            $table->decimal('factor', 15, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_of_measure');
    }
};
