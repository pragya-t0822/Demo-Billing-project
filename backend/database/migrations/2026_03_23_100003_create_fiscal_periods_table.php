<?php

declare(strict_types = 1)
;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 20); // e.g., "Apr 2026"
            $table->string('fiscal_year', 10); // e.g., "2026-27"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['OPEN', 'CLOSING', 'CLOSED', 'LOCKED'])->default('OPEN');
            $table->foreignUuid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
