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
        Schema::table('chm_members', function (Blueprint $table) {
            // Drop existing email index if it exists
            $table->dropIndex(['email']);
            
            // Add unique constraint for email + site_id combination
            // Only apply to non-null emails to allow multiple members without email
            $table->unique(['email', 'site_id'], 'members_email_site_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chm_members', function (Blueprint $table) {
            $table->dropUnique('members_email_site_unique');
            
            // Recreate the original email index
            $table->index('email');
        });
    }
};
