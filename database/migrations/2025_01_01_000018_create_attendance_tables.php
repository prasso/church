<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Attendance Events (services, classes, etc.)
        if (!Schema::hasTable('chm_attendance_events')) {
             Schema::create('chm_attendance_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('chm_locations');
            $table->foreignId('event_type_id')->constrained('chm_event_types');
            $table->foreignId('ministry_id')->nullable()->constrained('chm_ministries');
            $table->foreignId('group_id')->nullable()->constrained('chm_groups');
            $table->integer('expected_attendance')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('requires_check_in')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly, etc.
            $table->json('recurrence_details')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
        }
        if (!Schema::hasTable('chm_attendance_records')) {
        // Attendance Records
        Schema::create('chm_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('chm_attendance_events');
            $table->foreignId('member_id')->nullable()->constrained('chm_members');
            $table->foreignId('family_id')->nullable()->constrained('chm_families');
            $table->foreignId('checked_in_by')->nullable()->constrained('users');
            $table->dateTime('check_in_time');
            $table->dateTime('check_out_time')->nullable();
            $table->string('status')->default('present'); // present, late, excused, absent, etc.
            $table->integer('guest_count')->default(0);
            $table->text('notes')->nullable();
            $table->string('source')->default('manual'); // manual, kiosk, mobile, import, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['event_id', 'member_id']);
            $table->index(['member_id', 'check_in_time']);
        });
    }
    if (!Schema::hasTable('chm_attendance_groups')) {
        // Attendance Groups (for grouping events)
        Schema::create('chm_attendance_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('ministry_id')->nullable()->constrained('chm_ministries');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }
    
    if (!Schema::hasTable('chm_attendance_group_members')) {
        // Pivot table for attendance group membership
        Schema::create('chm_attendance_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('chm_attendance_groups');
            $table->morphs('attendable'); // Can be member, family, or group
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['group_id', 'attendable_id', 'attendable_type'], 'chm_att_grp_mem_unique');
        });
    }
    
    if (!Schema::hasTable('chm_attendance_summaries')) {
        // Attendance Summaries (pre-calculated for reporting)
        Schema::create('chm_attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('summary_date');
            $table->foreignId('event_id')->nullable()->constrained('chm_attendance_events');
            $table->foreignId('ministry_id')->nullable()->constrained('chm_ministries');
            $table->foreignId('group_id')->nullable()->constrained('chm_attendance_groups');
            $table->integer('total_attended')->default(0);
            $table->integer('total_members')->default(0);
            $table->integer('total_guests')->default(0);
            $table->integer('total_absent')->default(0);
            $table->decimal('attendance_rate', 5, 2)->default(0);
            $table->json('demographics')->nullable(); // Age groups, gender, etc.
            $table->timestamps();
            
            // Indexes for reporting
            $table->index(['summary_date', 'ministry_id']);
            $table->index(['event_id', 'summary_date']);
        });
    }
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chm_attendance_summaries');
        Schema::dropIfExists('chm_attendance_group_members');
        Schema::dropIfExists('chm_attendance_groups');
        Schema::dropIfExists('chm_attendance_records');
        Schema::dropIfExists('chm_attendance_events');
    }
}
