<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_location_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->foreignId('site_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('work_type')->nullable();
            $table->unsignedBigInteger('work_id')->nullable();
            $table->text('work_description')->nullable();
            $table->decimal('progress', 5, 1)->default(0);
            $table->unsignedInteger('worker_count')->default(0);
            $table->text('issues')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('draft');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_report_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->boolean('posted')->default(false);
            $table->timestamps();
        });

        Schema::create('daily_report_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->decimal('working_hours', 6, 2)->nullable();
            $table->decimal('fuel_used', 10, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_report_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->string('attendance_status')->default('present');
            $table->timestamps();
        });

        Schema::create('daily_report_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });

        Schema::table('equipment_usage', function (Blueprint $table) {
            $table->foreignId('daily_report_id')->nullable()->after('project_location_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment_usage', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daily_report_id');
        });
        Schema::dropIfExists('daily_report_photos');
        Schema::dropIfExists('daily_report_workers');
        Schema::dropIfExists('daily_report_equipment');
        Schema::dropIfExists('daily_report_materials');
        Schema::dropIfExists('daily_reports');
    }
};
