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
        Schema::create('chm_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('chm_members')->onDelete('set null');
            $table->string('reference_id')->nullable()->comment('External reference ID (e.g., Stripe payment intent ID)');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->nullable()->comment('e.g., credit_card, bank_transfer, cash');
            $table->string('transaction_type')->comment('e.g., tithe, offering, donation, pledge_payment');
            $table->string('fund_id')->nullable()->comment('Optional fund/category');
            $table->date('transaction_date');
            $table->date('posted_date')->nullable();
            $table->string('status')->default('pending')->comment('pending, completed, failed, refunded');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('member_id');
            $table->index('reference_id');
            $table->index('transaction_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_transactions');
    }
};
