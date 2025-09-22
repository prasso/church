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
        if (!Schema::hasTable('chm_event_types')) {
            Schema::create('chm_event_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color')->nullable(); // For UI display
            $table->string('icon')->nullable(); // For UI display
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        }

        // Create indexes separately to handle existing tables
        if (Schema::hasTable('chm_event_types')) {
            try {
                Schema::table('chm_event_types', function (Blueprint $table) {
                    $table->index('slug');
                    $table->index('is_active');
                    $table->index('sort_order');
                });
            } catch (\Exception $e) {
                // Indexes might already exist, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chm_event_types');
    }
};
