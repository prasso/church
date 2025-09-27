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
        if (!Schema::hasTable('chm_locations')) {
            Schema::create('chm_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('US');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('capacity')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            });
        }

        // Create indexes separately to handle existing tables
        if (Schema::hasTable('chm_locations')) {
            try {
                Schema::table('chm_locations', function (Blueprint $table) {
                    $table->index('name');
                    $table->index('city');
                    $table->index('state');
                    $table->index('is_active');
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
        Schema::dropIfExists('chm_locations');
    }
};
