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
        Schema::create('chm_event_occurrences', function (Blueprint $table) {
            $table->id();
            
            // Relationship to the parent event
            $table->foreignId('event_id')->constrained('chm_events')->onDelete('cascade');
            
            // Specific occurrence details (may differ from parent event)
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('location_override')->nullable();
            
            // Status specific to this occurrence
            $table->string('status')->default('scheduled'); // scheduled, cancelled, completed
            $table->text('cancellation_reason')->nullable();
            
            // Attendance tracking
            $table->integer('attendance_count')->default(0);
            
            // Metadata for this specific occurrence
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('event_id');
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_event_occurrences');
    }
};
