<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only audit log — never modified or deleted
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('log_number', 30)->unique(); // e.g., AL-2026-00001
            $table->foreignUuid('user_id')->nullable()->constrained('users');
            $table->string('store_id')->nullable(); // store context
            $table->string('action', 50); // POST_JOURNAL, CONFIRM_INVOICE, etc.
            $table->string('entity_type', 50); // JournalEntry, Invoice, etc.
            $table->string('entity_id'); // UUID of the acted-upon entity
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('severity', ['INFO', 'WARNING', 'CRITICAL'])->default('INFO');
            $table->timestamp('logged_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'logged_at']);
            $table->index('logged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
