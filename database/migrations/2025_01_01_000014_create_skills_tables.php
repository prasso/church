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
        // Skills table
        Schema::create('chm_skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('category');
            $table->index('is_active');
        });

        // Member skills pivot table
        Schema::create('chm_member_skill', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('member_id')
                ->constrained('chm_members')
                ->onDelete('cascade');
                
            $table->foreignId('skill_id')
                ->constrained('chm_skills')
                ->onDelete('cascade');
            
            // Pivot data
            $table->string('proficiency_level')->nullable(); // beginner, intermediate, advanced, expert
            $table->integer('years_experience')->nullable();
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Composite unique key
            $table->unique(['member_id', 'skill_id']);
        });

        // Position skills pivot table
        Schema::create('chm_position_skill', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('position_id')
                ->constrained('chm_volunteer_positions')
                ->onDelete('cascade');
                
            $table->foreignId('skill_id')
                ->constrained('chm_skills')
                ->onDelete('cascade');
            
            // Pivot data
            $table->boolean('is_required')->default(false);
            $table->string('proficiency_required')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Composite unique key
            $table->unique(['position_id', 'skill_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_position_skill');
        Schema::dropIfExists('chm_member_skill');
        Schema::dropIfExists('chm_skills');
    }
};
