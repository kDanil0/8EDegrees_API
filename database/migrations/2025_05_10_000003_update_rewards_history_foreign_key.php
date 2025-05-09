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
        Schema::table('rewards_history', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['reward_id']);
            
            // Add the constraint back with nullOnDelete
            $table->foreign('reward_id')
                  ->references('id')
                  ->on('rewards')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards_history', function (Blueprint $table) {
            // Drop the modified foreign key constraint
            $table->dropForeign(['reward_id']);
            
            // Add back the original constraint
            $table->foreign('reward_id')
                  ->references('id')
                  ->on('rewards');
        });
    }
}; 