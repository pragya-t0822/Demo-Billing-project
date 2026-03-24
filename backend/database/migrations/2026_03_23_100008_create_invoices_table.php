<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number', 30)->unique(); // INV-2026-00001
            $table->foreignUuid('store_id')->constrained('stores');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers');
            $table->foreignUuid('fiscal_period_id')->constrained('fiscal_periods');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            // Amounts
            $table->decimal('subtotal', 12, 2)->default(0);       // sum of line item base prices
            $table->decimal('total_discount', 12, 2)->default(0); // total discount applied
            $table->decimal('taxable_amount', 12, 2)->default(0); // subtotal - discount
            $table->decimal('cgst_total', 12, 2)->default(0);
            $table->decimal('sgst_total', 12, 2)->default(0);
            $table->decimal('igst_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('outstanding_balance', 12, 2)->default(0);

            // Supply type for GST
            $table->enum('supply_type', ['INTRA', 'INTER'])->default('INTRA');

            $table->enum('status', ['DRAFT', 'CONFIRMED', 'PAID', 'PARTIAL', 'CANCELLED'])->default('DRAFT');
            $table->enum('payment_mode', ['CASH', 'CARD', 'UPI', 'NETBANKING', 'CHEQUE', 'CREDIT', 'MIXED'])->nullable();
            $table->text('notes')->nullable();

            // Linked accounting
            $table->string('journal_entry_id')->nullable(); // JE after confirm

            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'status', 'invoice_date']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products');
            $table->string('product_name'); // snapshot at time of invoice
            $table->string('hsn_code', 20);

            // Quantity/Weight
            $table->decimal('quantity', 12, 3)->default(1);       // units or grams
            $table->decimal('gross_weight_grams', 10, 3)->nullable();
            $table->decimal('net_weight_grams', 10, 3)->nullable();

            // Pricing
            $table->decimal('unit_price', 12, 2)->default(0);    // rate per unit/gram
            $table->decimal('making_charges', 10, 2)->default(0);
            $table->decimal('wastage_amount', 10, 2)->default(0);
            $table->decimal('hallmark_charge', 8, 2)->default(0);
            $table->decimal('base_price', 12, 2)->default(0);    // total before discount
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->default(0); // base_price - discount

            // GST per line item (mandatory)
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);
            $table->decimal('total_tax', 10, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);    // taxable + tax

            // Metal rate snapshot for weight-based items
            $table->decimal('metal_rate_per_gram', 10, 2)->nullable();
            $table->timestamp('metal_rate_timestamp')->nullable();

            $table->timestamps();
        });

        // Credit notes for cancelled invoices
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('credit_note_number', 30)->unique(); // CN-2026-00001
            $table->foreignUuid('invoice_id')->constrained('invoices');
            $table->foreignUuid('store_id')->constrained('stores');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers');
            $table->date('credit_note_date');
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->enum('status', ['ISSUED', 'APPLIED', 'VOID'])->default('ISSUED');
            $table->string('journal_entry_id')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
        });

        // Payments against invoices
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payment_number', 30)->unique(); // PAY-2026-00001
            $table->foreignUuid('invoice_id')->constrained('invoices');
            $table->foreignUuid('store_id')->constrained('stores');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers');
            $table->enum('payment_mode', ['CASH', 'CARD', 'UPI', 'NETBANKING', 'CHEQUE', 'CREDIT']);
            $table->decimal('amount_paid', 12, 2);
            $table->string('gateway_transaction_id')->nullable();
            $table->string('cheque_number', 30)->nullable();
            $table->string('bank_reference')->nullable();
            $table->enum('status', ['PENDING', 'CONFIRMED', 'FAILED', 'REFUNDED'])->default('CONFIRMED');
            $table->string('journal_entry_id')->nullable();
            $table->foreignUuid('recorded_by')->constrained('users');
            $table->date('payment_date');
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoices');
    }
};
