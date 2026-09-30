<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pillars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_location_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->date('start_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->string('status')->default('not_started');
            $table->decimal('progress', 5, 1)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'code']);
        });

        Schema::create('walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_location_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->decimal('length', 10, 2)->default(0);
            $table->decimal('height', 10, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->string('status')->default('not_started');
            $table->decimal('progress', 5, 1)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'code']);
        });

        Schema::create('bridges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_location_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->decimal('bridge_length', 10, 2)->default(0);
            $table->decimal('bridge_width', 10, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->string('status')->default('not_started');
            $table->decimal('progress', 5, 1)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bridges');
        Schema::dropIfExists('walls');
        Schema::dropIfExists('pillars');
    }
};
