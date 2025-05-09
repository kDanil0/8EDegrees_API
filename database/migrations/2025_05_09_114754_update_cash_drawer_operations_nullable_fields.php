<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_drawer_operations', function (Blueprint $table) {
            // Make the calculated fields nullable
            $table->decimal('expected_cash', 10, 2)->nullable()->default(null)->change();
            $table->decimal('short_over', 10, 2)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_drawer_operations', function (Blueprint $table) {
            // Revert back to default(0)
            $table->decimal('expected_cash', 10, 2)->default(0)->change();
            $table->decimal('short_over', 10, 2)->default(0)->change();
        });
    }
};
