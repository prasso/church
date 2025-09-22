<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('aph_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('report_type');
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('aph_report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('aph_reports')->onDelete('cascade');
            $table->string('frequency'); // daily, weekly, monthly
            $table->string('time')->default('09:00');
            $table->string('day_of_week')->nullable(); // For weekly
            $table->string('day_of_month')->nullable(); // For monthly
            $table->json('recipients'); // Array of email addresses
            $table->string('format')->default('pdf'); // pdf, csv, xlsx
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('aph_report_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('aph_reports')->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained('aph_report_schedules')->onDelete('set null');
            $table->string('status'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->string('file_path')->nullable();
            $table->json('parameters')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('aph_report_runs');
        Schema::dropIfExists('aph_report_schedules');
        Schema::dropIfExists('aph_reports');
    }
};
