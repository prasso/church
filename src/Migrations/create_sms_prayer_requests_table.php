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
        Schema::create('chm_sms_prayer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('msg_inbound_message_id')->nullable()->constrained('msg_inbound_messages')->onDelete('set null');
            $table->foreignId('msg_guest_id')->nullable()->constrained('msg_guests')->onDelete('set null');
            $table->foreignId('prayer_request_id')->nullable()->constrained('chm_prayer_requests')->onDelete('set null');
            $table->text('content');
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('status')->default('new');
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('is_processed');
            $table->index('campaign_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_sms_prayer_requests');
    }
};
