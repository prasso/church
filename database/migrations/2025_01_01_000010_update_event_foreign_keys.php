<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check and drop existing foreign key constraints if they exist
        $databaseName = config('database.connections.mysql.database');
        $tableName = 'chm_events';

        // Check if created_by foreign key exists
        $createdByFkExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'created_by' AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$databaseName, $tableName]);

        if (!empty($createdByFkExists)) {
            Schema::table('chm_events', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
            });
        }

        // Check if ministry_id foreign key exists
        $ministryFkExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'ministry_id' AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$databaseName, $tableName]);

        if (!empty($ministryFkExists)) {
            Schema::table('chm_events', function (Blueprint $table) {
                $table->dropForeign(['ministry_id']);
            });
        }

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

            // Note: The original ministry_id foreign key would have referenced chm_ministries
            // but we'll leave this commented out since we want to use chm_ministries
            /*
            $table->foreign('ministry_id')
                ->references('id')
                ->on('chm_ministries')
                ->onDelete('set null');
            */
        });
    }
};
