<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices');
            $table->foreignUuid('customer_id')->constrained('customers');
            $table->foreignUuid('store_id')->constrained('stores');
            $table->decimal('outstanding_balance', 12, 2);
            $table->date('due_date');
            $table->integer('days_overdue')->default(0);
            $table->integer('recovery_stage')->default(1); // 1-5
            $table->enum('status', ['ACTIVE', 'CLOSED', 'PAUSED', 'LEGAL', 'WRITTEN_OFF'])->default('ACTIVE');
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->date('promise_to_pay_date')->nullable();
            $table->decimal('promise_to_pay_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->unique('invoice_id');
            $table->index(['status', 'days_overdue']);
        });

        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recovery_record_id')->constrained('recovery_records')->cascadeOnDelete();
            $table->foreignUuid('invoice_id')->constrained('invoices');
            $table->integer('reminder_stage');
            $table->enum('channel', ['WHATSAPP', 'EMAIL', 'SMS']);
            $table->enum('status', ['SENT', 'FAILED', 'QUEUED', 'DELIVERED'])->default('QUEUED');
            $table->text('message_content');
            $table->string('gateway_message_id')->nullable();
            $table->string('sent_by'); // user_id or SYSTEM
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('link_number', 30)->unique(); // PL-2026-00001
            $table->foreignUuid('invoice_id')->constrained('invoices');
            $table->foreignUuid('customer_id')->constrained('customers');
            $table->string('short_url');
            $table->decimal('amount', 12, 2);
            $table->json('payment_methods')->nullable(); // ['UPI', 'CARD', etc.]
            $table->timestamp('expires_at');
            $table->enum('status', ['ACTIVE', 'USED', 'EXPIRED', 'CANCELLED'])->default('ACTIVE');
            $table->string('payment_id')->nullable(); // set after payment
            $table->string('created_by'); // user_id or SYSTEM
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_links');
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('recovery_records');
    }
};
