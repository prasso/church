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
        if (!Schema::hasTable('chm_attendances')) {
            Schema::create('chm_attendances', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('occurrence_id')->constrained('chm_event_occurrences')->onDelete('cascade');
            $table->foreignId('member_id')->nullable()->constrained('chm_members')->onDelete('cascade');
            $table->foreignId('family_id')->nullable()->constrained('chm_families')->onDelete('set null');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Attendance details
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->string('status')->default('present'); // present, late, excused, absent
            $table->text('notes')->nullable();
            
            // For guests
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
        });
        }

        // Create indexes separately to handle existing tables
        if (Schema::hasTable('chm_attendances')) {
            try {
                Schema::table('chm_attendances', function (Blueprint $table) {
                    $table->index('occurrence_id');
                    $table->index('member_id');
                    $table->index('family_id');
                    $table->index('status');
                    $table->index('check_in_time');
                });
            } catch (\Exception $e) {
                // Indexes might already exist, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_attendances');
    }
};
