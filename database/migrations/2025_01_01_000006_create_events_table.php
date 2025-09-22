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
        if (!Schema::hasTable('chm_events')) {
            Schema::create('chm_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('service'); // service, meeting, event, etc.
            $table->string('location')->nullable();
            $table->string('image_url')->nullable();
            
            // Recurrence settings
            $table->string('recurrence_pattern')->nullable(); // none, daily, weekly, monthly, yearly, custom
            $table->json('recurrence_days')->nullable(); // For weekly patterns: [1,3,5] for Mon, Wed, Fri
            $table->integer('recurrence_interval')->default(1); // Every X days/weeks/months
            $table->date('start_date');
            $table->time('start_time');
            $table->date('end_date')->nullable(); // Null means no end date
            $table->time('end_time')->nullable();
            
            // Capacity and registration
            $table->integer('capacity')->nullable();
            $table->boolean('requires_registration')->default(false);
            $table->dateTime('registration_deadline')->nullable();
            
            // Status
            $table->string('status')->default('draft'); // draft, published, cancelled, completed
            
            // Relationships
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('ministry_id')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_events');
    }
};
