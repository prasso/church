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
        Schema::create('chm_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->nullable()->constrained('chm_families');
            $table->foreignId('user_id')->nullable()->constrained('users');
            
            // Personal Information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('United States');
            
            // Demographics
            $table->date('birthdate')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed', 'separated'])->nullable();
            $table->date('anniversary')->nullable();
            
            // Church Information
            $table->date('baptism_date')->nullable();
            $table->date('membership_date')->nullable();
            $table->enum('membership_status', ['visitor', 'regular_attendee', 'member', 'inactive', 'removed'])->default('visitor');
            
            // Relationships
            $table->boolean('is_head_of_household')->default(false);
            
            // Additional
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['last_name', 'first_name']);
            $table->index('membership_status');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_members');
    }
};
