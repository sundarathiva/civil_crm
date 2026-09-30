<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('equipment_type');
            $table->string('registration_number')->nullable();
            $table->string('owner')->nullable();
            $table->string('operator_name')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('available');
            $table->timestamps();
        });

        Schema::create('equipment_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_location_id')->nullable()->constrained()->nullOnDelete();
            $table->date('used_on');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('working_hours', 6, 2)->nullable();
            $table->decimal('fuel_used', 10, 2)->nullable();
            $table->string('fuel_unit')->default('litres');
            $table->text('work_description')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_usage');
        Schema::dropIfExists('equipment');
    }
};
