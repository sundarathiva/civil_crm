<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('project_type_id')->constrained();
            $table->string('client_name');
            $table->string('location_summary')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->foreignId('engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('site_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status')->default('new');
            $table->decimal('progress', 5, 1)->default(0);
            $table->timestamps();
        });

        Schema::create('project_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('chainage')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('site_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('project_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('project_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_on');
            $table->decimal('pillar_progress', 5, 1)->default(0);
            $table->decimal('wall_progress', 5, 1)->default(0);
            $table->decimal('bridge_progress', 5, 1)->default(0);
            $table->decimal('overall_progress', 5, 1)->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_progress');
        Schema::dropIfExists('project_status_history');
        Schema::dropIfExists('project_locations');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('project_types');
    }
};
