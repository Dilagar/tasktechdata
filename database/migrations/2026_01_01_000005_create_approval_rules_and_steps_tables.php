<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('document_type', 20); // PR or PO
            $table->decimal('min_amount', 15, 2)->default(0.00);
            $table->decimal('max_amount', 15, 2)->nullable(); // Nullable = infinity
            $table->string('category', 50)->nullable(); // Nullable = any category
            $table->string('rule_type', 20)->default('BASE'); // BASE or EXTENSION
            $table->string('insert_after_role', 50)->nullable(); // Used by EXTENSION rules
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'document_type', 'is_active']);
        });

        Schema::create('approval_rule_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_rule_id')->constrained('approval_rules')->cascadeOnDelete();
            $table->integer('sequence');
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['approval_rule_id', 'sequence']);
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('document_type', 20); // PR, PO
            $table->unsignedBigInteger('document_id');
            $table->string('status', 30)->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->integer('current_step')->default(1);
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->integer('sequence');
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('status', 30)->default('PENDING'); // PENDING, APPROVED, REJECTED, SKIPPED
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['approval_request_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_rule_steps');
        Schema::dropIfExists('approval_rules');
    }
};
