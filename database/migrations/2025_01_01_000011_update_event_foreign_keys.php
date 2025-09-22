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
        // Drop existing foreign key constraints
        Schema::table('chm_events', function (Blueprint $table) {
            $table->dropForeign(['ministry_id']);
        });

        // Recreate the foreign key constraint with the correct table name
        Schema::table('chm_events', function (Blueprint $table) {
            $table->foreign('ministry_id')
                ->references('id')
                ->on('chm_ministries')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint
        Schema::table('chm_events', function (Blueprint $table) {
            $table->dropForeign(['ministry_id']);
        });

        // Recreate the original foreign key constraint (if needed)
        Schema::table('chm_events', function (Blueprint $table) {
            $table->foreign('ministry_id')
                ->references('id')
                ->on('chm_ministries')
                ->onDelete('set null');
        });
    }
};
