<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('category');
            $table->string('unit');
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('material_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('opening_qty', 12, 2)->default(0);
            $table->decimal('received_qty', 12, 2)->default(0);
            $table->decimal('issued_qty', 12, 2)->default(0);
            $table->decimal('used_qty', 12, 2)->default(0);
            $table->decimal('returned_qty', 12, 2)->default(0);
            $table->decimal('current_qty', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('material_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('quantity', 12, 2);
            $table->date('transacted_on');
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('reference');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_transactions');
        Schema::dropIfExists('material_stock');
        Schema::dropIfExists('materials');
    }
};
