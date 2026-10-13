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
        Schema::table('classes', function (Blueprint $table) {
            $table->time('jam_masuk')->default('07:00:00')->after('grade_level');
            $table->time('batas_terlambat')->default('07:15:00')->after('jam_masuk');
            $table->time('batas_alpa')->default('08:30:00')->after('batas_terlambat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['jam_masuk', 'batas_terlambat', 'batas_alpa']);
        });
    }
};
