<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chm_attendance_events')) {
            Schema::table('chm_attendance_events', function (Blueprint $table) {
                if (!Schema::hasColumn('chm_attendance_events', 'attendance_group_id')) {
                    $table->foreignId('attendance_group_id')
                        ->nullable()
                        ->after('group_id')
                        ->constrained('chm_attendance_groups');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chm_attendance_events')) {
            Schema::table('chm_attendance_events', function (Blueprint $table) {
                if (Schema::hasColumn('chm_attendance_events', 'attendance_group_id')) {
                    $table->dropForeign(['attendance_group_id']);
                    $table->dropColumn('attendance_group_id');
                }
            });
        }
    }
};
