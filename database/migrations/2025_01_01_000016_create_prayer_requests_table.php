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
        Schema::create('chm_prayer_requests', function (Blueprint $table) {
            $table->id();
            
            // Basic information
            $table->string('title');
            $table->text('description');
            
            // Relationships
            $table->foreignId('member_id')
                ->nullable()
                ->constrained('chm_members')
                ->onDelete('set null');
                
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('chm_members')
                ->onDelete('set null');
            
            // Visibility and status
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_public')->default(true);
            $table->string('status')->default('active'); // active, answered, inactive
            
            // Tracking
            $table->unsignedInteger('prayer_count')->default(0);
            
            // Answer details
            $table->text('answer')->nullable();
            $table->timestamp('answered_at')->nullable();
            
            // Metadata for additional fields
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('member_id');
            $table->index('requested_by');
            $table->index('status');
            $table->index('is_public');
            $table->index('created_at');
        });
        
        // Pivot table for prayer groups and prayer requests
        Schema::create('chm_prayer_group_requests', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('group_id')
                ->constrained('chm_groups')
                ->onDelete('cascade');
                
            $table->foreignId('prayer_request_id')
                ->constrained('chm_prayer_requests')
                ->onDelete('cascade');
            
            // Timestamps
            $table->timestamps();
            
            // Composite unique key
            $table->unique(['group_id', 'prayer_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_prayer_group_requests');
        Schema::dropIfExists('chm_prayer_requests');
    }
};
