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
        Schema::table('products', function (Blueprint $table) {
            // First, add the new category_id column
            $table->foreignId('category_id')->nullable()->after('quantity')->constrained();
            
            // Remove the old category column
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add back the original category column
            $table->string('category', 30)->after('quantity')->nullable();
            
            // Drop the foreign key constraint and the category_id column
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
}; 