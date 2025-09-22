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
        Schema::create('chm_availabilities', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('member_id')
                ->constrained('chm_members')
                ->onDelete('cascade');
            
            // Time details
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            
            // Recurring availability
            $table->boolean('recurring')->default(true);
            $table->tinyInteger('day_of_week')->nullable()
                ->comment('0-6 (Sunday-Saturday) or null for specific dates');
                
            // Timezone
            $table->string('timezone', 50)->default('UTC');
            
            // Notes
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('member_id');
            $table->index('recurring');
            $table->index('day_of_week');
            $table->index(['start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_availabilities');
    }
};
