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
        if (!Schema::hasTable('chm_volunteer_positions')) {
            Schema::create('chm_volunteer_positions', function (Blueprint $table) {
            $table->id();
            
            // Basic information
            $table->string('title');
            $table->text('description')->nullable();
            
            // Relationships
            $table->foreignId('ministry_id')
                ->nullable()
                ->constrained('chm_ministries')
                ->onDelete('cascade');
                
            $table->foreignId('group_id')
                ->nullable()
                ->constrained('chm_groups')
                ->onDelete('cascade');
            
            // Position details
            $table->json('skills_required')->nullable();
            $table->string('time_commitment')->nullable();
            $table->string('location')->nullable();
            
            // Status and limits
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_volunteers')->nullable();
            
            // Date range
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // Metadata for additional fields
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('ministry_id');
            $table->index('group_id');
            $table->index('is_active');
        });
    }
    if (!Schema::hasTable('chm_volunteer_assignments')) {
        
        // Create volunteer_assignments table
        Schema::create('chm_volunteer_assignments', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('member_id')
                ->constrained('chm_members')
                ->onDelete('cascade');
                
            $table->foreignId('position_id')
                ->constrained('chm_volunteer_positions')
                ->onDelete('cascade');
            
            // Assignment details
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active, inactive, pending, completed
            $table->text('notes')->nullable();
            
            // Assignment metadata
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
                
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
                
            $table->date('trained_on')->nullable();
            
            // Metadata for additional fields
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Composite unique key
            $table->unique(['member_id', 'position_id', 'start_date'], 'chm_vol_assignments_unique');
            
            // Indexes
            $table->index(['member_id', 'status']);
            $table->index(['position_id', 'status']);
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_volunteer_assignments');
        Schema::dropIfExists('chm_volunteer_positions');
    }
};
