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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 20)->unique();
            $table->string('name', 50);
            $table->text('description', 255)->nullable();
            $table->integer('quantity')->default(0);
            $table->string('category', 30);
            $table->integer('reorderLevel')->default(10);
            $table->float('price', 8, 2);
            $table->string('status', 20);
            $table->date('expiryDate')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
}; 