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
        Schema::create('chm_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Relationships
            $table->foreignId('ministry_id')
                ->nullable()
                ->constrained('chm_ministries')
                ->onDelete('set null');
                
            $table->foreignId('contact_person_id')
                ->nullable()
                ->constrained('chm_members')
                ->onDelete('set null');
            
            // Meeting information
            $table->string('meeting_schedule')->nullable();
            $table->string('meeting_location')->nullable();
            
            // Dates
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            // Settings
            $table->unsignedInteger('max_members')->nullable();
            $table->boolean('is_open')->default(true);
            $table->boolean('requires_approval')->default(false);
            
            // Metadata for additional fields
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('ministry_id');
            $table->index('contact_person_id');
            $table->index('is_open');
        });

        // Create group_member pivot table
        Schema::create('chm_group_member', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('group_id')
                ->constrained('chm_groups')
                ->onDelete('cascade');
                
            $table->foreignId('member_id')
                ->constrained('chm_members')
                ->onDelete('cascade');
            
            // Pivot data
            $table->string('role')->default('member'); // leader, co-leader, member, etc.
            $table->date('join_date');
            $table->date('leave_date')->nullable();
            $table->string('status')->default('active'); // active, inactive, pending, removed
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Composite unique key
            $table->unique(['group_id', 'member_id']);
            
            // Indexes
            $table->index(['group_id', 'status']);
            $table->index(['member_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_group_member');
        Schema::dropIfExists('chm_groups');
    }
};
