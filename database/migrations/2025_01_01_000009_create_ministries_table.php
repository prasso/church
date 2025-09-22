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
        Schema::create('chm_ministries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('leader_id')->nullable()->constrained('chm_members')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('chm_ministries')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->string('meeting_schedule')->nullable();
            $table->string('meeting_location')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Pivot table for ministry members
        Schema::create('chm_ministry_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained('chm_ministries')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('chm_members')->onDelete('cascade');
            $table->string('role')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['ministry_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_ministry_member');
        Schema::dropIfExists('chm_ministries');
    }
};
