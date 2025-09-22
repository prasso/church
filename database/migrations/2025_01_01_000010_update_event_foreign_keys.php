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
            // This will drop the constraint if it exists
            $table->dropForeign(['created_by']);
            $table->dropForeign(['ministry_id']);
        });

        // Recreate the foreign key constraints with the correct table names
        Schema::table('chm_events', function (Blueprint $table) {
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

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
        // Drop the foreign key constraints
        Schema::table('chm_events', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['ministry_id']);
        });

        // Recreate the original foreign key constraints (if needed)
        Schema::table('chm_events', function (Blueprint $table) {
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Note: The original ministry_id foreign key would have referenced aph_ministries
            // but we'll leave this commented out since we want to use chm_ministries
            /*
            $table->foreign('ministry_id')
                ->references('id')
                ->on('aph_ministries')
                ->onDelete('set null');
            */
        });
    }
};
