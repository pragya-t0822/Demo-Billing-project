<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal entry header — immutable once POSTED
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entry_number', 30)->unique(); // e.g., JE-2026-00001
            $table->foreignUuid('fiscal_period_id')->constrained('fiscal_periods');
            $table->foreignUuid('store_id')->constrained('stores');
            $table->date('entry_date');
            $table->enum('reference_type', [
                'INVOICE', 'PAYMENT', 'SETTLEMENT', 'ADJUSTMENT',
                'REVERSAL', 'OPENING', 'CLOSING', 'DEPRECIATION', 'GST_SETTLEMENT'
            ]);
            $table->string('reference_id')->nullable(); // invoice_id, payment_id, etc.
            $table->string('narration');
            $table->decimal('total_debit', 14, 2)->default(0);
            $table->decimal('total_credit', 14, 2)->default(0);
            $table->enum('status', ['POSTED'])->default('POSTED'); // always POSTED, no drafts
            $table->string('reversed_by')->nullable(); // journal_entry_id of reversal
            $table->boolean('is_reversed')->default(false);
            $table->foreignUuid('posted_by')->constrained('users');
            $table->timestamps(); // created_at = posted_at
        });

        // Journal entry lines — debit/credit lines
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->string('account_code', 20);
            $table->foreign('account_code')->references('code')->on('chart_of_accounts');
            $table->decimal('debit_amount', 14, 2)->default(0);
            $table->decimal('credit_amount', 14, 2)->default(0);
            $table->string('cost_center')->nullable(); // store_id or department
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
    }
};
