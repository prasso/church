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
        Schema::table('chm_volunteer_assignments', function (Blueprint $table) {
            // Make member_id nullable to support guest signups
            $table->foreignId('member_id')
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chm_volunteer_assignments', function (Blueprint $table) {
            // Revert to non-nullable
            $table->foreignId('member_id')
                ->nullable(false)
                ->change();
        });
    }
};
