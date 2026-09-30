<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('mobile');
            $table->string('worker_type');
            $table->string('skill')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_location_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('daily_wage', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('worker_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('work_type')->nullable();
            $table->unsignedBigInteger('work_id')->nullable();
            $table->date('assigned_on');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('worker_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_location_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attended_on');
            $table->string('status');
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['worker_id', 'attended_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_attendance');
        Schema::dropIfExists('worker_assignments');
        Schema::dropIfExists('workers');
    }
};
