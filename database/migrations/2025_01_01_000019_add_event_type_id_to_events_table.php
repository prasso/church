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
        Schema::table('chm_events', function (Blueprint $table) {
            // Remove the old type column
            $table->dropColumn('type');

            // Add the event_type_id foreign key
            $table->foreignId('event_type_id')
                ->nullable()
                ->constrained('chm_event_types')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chm_events', function (Blueprint $table) {
            // Remove the foreign key
            $table->dropForeign(['event_type_id']);
            $table->dropColumn('event_type_id');

            // Add back the type column
            $table->string('type')->default('service');
        });
    }
};
