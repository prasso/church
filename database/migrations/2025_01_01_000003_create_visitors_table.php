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
        Schema::create('aph_visitors', function (Blueprint $table) {
            $table->id();
            
            // Visitor Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            
            // Address Information
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('United States');
            
            // Visit Information
            $table->date('visit_date');
            $table->string('how_did_you_hear')->nullable();
            $table->json('interests')->nullable();
            $table->text('notes')->nullable();
            
            // Follow-up Information
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->enum('status', [
                'new',
                'contacted',
                'scheduled_visit',
                'needs_follow_up',
                'converted',
                'not_interested'
            ])->default('new');
            
            // Conversion Information
            $table->boolean('converted_to_member')->default(false);
            $table->foreignId('converted_to_member_id')->nullable()->constrained('aph_members');
            $table->timestamp('converted_at')->nullable();
            
            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['last_name', 'first_name']);
            $table->index('status');
            $table->index('visit_date');
            $table->index('follow_up_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aph_visitors');
    }
};
