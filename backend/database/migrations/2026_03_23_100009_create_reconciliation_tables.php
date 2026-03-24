<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores');
            $table->string('bank_name');
            $table->string('account_number_masked', 20); // last 4 digits only
            $table->date('statement_date');
            $table->decimal('opening_balance', 14, 2);
            $table->decimal('closing_balance', 14, 2);
            $table->enum('status', ['IMPORTED', 'IN_PROGRESS', 'RECONCILED', 'PENDING_REVIEW'])->default('IMPORTED');
            $table->foreignUuid('imported_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('bank_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bank_statement_id')->constrained('bank_statements')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('narration');
            $table->decimal('credit_amount', 14, 2)->default(0);
            $table->decimal('debit_amount', 14, 2)->default(0);
            $table->decimal('running_balance', 14, 2)->default(0);
            $table->string('reference_number')->nullable(); // bank UTR/cheque number
            $table->enum('status', ['PENDING', 'MATCHED', 'RECONCILED', 'DISPUTED', 'ADJUSTED'])->default('PENDING');
            $table->timestamps();

            $table->index(['bank_statement_id', 'status']);
        });

        Schema::create('reconciliation_matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bank_entry_id')->constrained('bank_entries');
            $table->string('system_payment_id'); // payment.id
            $table->enum('match_confidence', ['HIGH', 'MEDIUM', 'LOW', 'MANUAL']);
            $table->json('match_criteria'); // array of matched fields
            $table->enum('status', ['MATCHED', 'CONFIRMED', 'DISPUTED'])->default('MATCHED');
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('gateway_settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores');
            $table->string('gateway'); // RAZORPAY, PAYTM, etc.
            $table->string('gateway_txn_id')->unique();
            $table->string('system_payment_id')->nullable();
            $table->date('settlement_date');
            $table->string('settlement_utr')->nullable(); // bank UTR for matching
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('fee_rate', 6, 4)->default(0); // e.g., 0.0200 = 2%
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->decimal('gst_on_fee', 10, 2)->default(0); // 18% GST on gateway fee
            $table->decimal('net_settled', 12, 2)->default(0);
            $table->enum('status', ['PENDING', 'SETTLED', 'REVERSED', 'DISPUTED'])->default('PENDING');
            $table->string('journal_entry_id')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'settlement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_settlements');
        Schema::dropIfExists('reconciliation_matches');
        Schema::dropIfExists('bank_entries');
        Schema::dropIfExists('bank_statements');
    }
};
