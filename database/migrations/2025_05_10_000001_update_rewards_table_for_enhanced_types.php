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
        Schema::table('rewards', function (Blueprint $table) {
            // Add new columns for enhanced reward types
            $table->enum('type', ['percentage_discount', 'free_item'])->after('description')->default('percentage_discount');
            $table->decimal('value', 5, 2)->after('type')->nullable()->comment('For percentage discounts, this is the percentage value');
            $table->foreignId('product_id')->after('value')->nullable()->comment('For free item rewards, this references the product')->constrained('products')->nullOnDelete();
            $table->boolean('is_active')->after('pointsNeeded')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['type', 'value', 'product_id', 'is_active']);
        });
    }
}; 