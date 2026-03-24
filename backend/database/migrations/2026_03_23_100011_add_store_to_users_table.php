<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 20)->nullable()->after('email');
            $table->string('phone', 15)->nullable()->after('employee_code');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->softDeletes();
        });

        // User can be assigned to multiple stores (pivot — no separate id needed)
        Schema::create('user_store_assignments', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->primary(['user_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_store_assignments');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employee_code', 'phone', 'is_active', 'deleted_at']);
        });
    }
};
