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
            $table->boolean('is_active')->default(true);
            $table->string('mail_host')->nullable();
            $table->integer('mail_port')->nullable()->default(587);
            $table->string('mail_username')->nullable();
            $table->string('mail_password')->nullable();
            $table->string('mail_encryption')->nullable()->default('tls');
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable()->default('SMAN 2 Tondano');
            $table->boolean('send_on_hadir')->default(true);
            $table->boolean('send_on_terlambat')->default(true);
            $table->boolean('send_on_alpa')->default(true);
            $table->string('recipient_target')->default('ortu_dan_siswa');
            $table->string('admin_email')->nullable();
            $table->string('subject_hadir')->nullable();
            $table->string('subject_terlambat')->nullable();
            $table->string('admin_phone')->nullable();
            $table->text('template_hadir')->nullable();
            $table->text('template_terlambat')->nullable();
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
