<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique(); // e.g., 1001, 4001
            $table->string('name');
            $table->enum('type', ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'COGS', 'EXPENSE', 'TAX']);
            // Normal balance: ASSET/COGS/EXPENSE = DEBIT; LIABILITY/EQUITY/REVENUE/TAX = CREDIT
            $table->enum('normal_balance', ['DEBIT', 'CREDIT']);
            $table->string('parent_code', 20)->nullable(); // hierarchical CoA
            $table->boolean('is_system_account')->default(false); // protected accounts
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
