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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('name')->unique();
            $table->string('grade_level');
            $table->time('jam_masuk')->default('07:00:00');
            $table->time('batas_terlambat')->default('07:15:00');
            $table->time('batas_alpa')->default('08:30:00');
            $table->timestamps();

            $table->index('grade_level');
            $table->index('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
