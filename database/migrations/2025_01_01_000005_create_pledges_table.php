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
        Schema::create('chm_pledges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('chm_members')->onDelete('cascade');
            $table->string('campaign_name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('frequency', ['one_time', 'weekly', 'biweekly', 'monthly', 'quarterly', 'annually'])->default('one_time');
            
            // Pledge period
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            // Status tracking
            $table->enum('status', ['active', 'fulfilled', 'cancelled', 'inactive'])->default('active');
            $table->date('last_payment_date')->nullable();
            $table->date('next_payment_date')->nullable();
            
            // Payment method details
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            
            // Metadata for additional fields
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('member_id');
            $table->index('status');
            $table->index('campaign_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_pledges');
    }
};
