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
        Schema::create('chm_pastoral_visits', function (Blueprint $table) {
            $table->id();
            
            // Visit details
            $table->string('title');
            $table->text('purpose');
            $table->dateTime('scheduled_for');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            
            // Location information
            $table->string('location_type'); // home, hospital, church, other
            $table->string('location_details')->nullable();
            
            // Relationships
            $table->foreignId('member_id')
                ->nullable()
                ->constrained('chm_members')
                ->onDelete('cascade');
                
            $table->foreignId('family_id')
                ->nullable()
                ->constrained('chm_families')
                ->onDelete('cascade');
                
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('chm_members')
                ->onDelete('set null');
            
            // Status tracking
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, canceled
            
            // Visit details
            $table->text('notes')->nullable();
            $table->text('follow_up_actions')->nullable();
            $table->dateTime('follow_up_date')->nullable();
            
            // Spiritual needs and outcomes
            $table->json('spiritual_needs')->nullable();
            $table->text('outcome_summary')->nullable();
            
            // Privacy and permissions
            $table->boolean('is_confidential')->default(false);
            
            // Metadata for additional fields
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_pastoral_visits');
    }
};
