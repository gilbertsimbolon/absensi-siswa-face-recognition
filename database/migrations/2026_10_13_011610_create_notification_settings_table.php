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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('wa_gateway_url')->default('http://127.0.0.1:3000');
            $table->string('wa_api_key')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('send_on_hadir')->default(true);
            $table->boolean('send_on_terlambat')->default(true);
            $table->string('recipient_target')->default('parent'); // 'parent', 'student', 'both'
            $table->string('admin_phone')->nullable();
            $table->text('template_hadir');
            $table->text('template_terlambat');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
