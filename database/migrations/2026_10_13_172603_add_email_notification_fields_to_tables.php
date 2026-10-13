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
        Schema::table('students', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->string('parent_email')->nullable()->after('parent_phone');
        });

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->string('mail_host')->nullable()->after('is_active');
            $table->integer('mail_port')->nullable()->default(587)->after('mail_host');
            $table->string('mail_username')->nullable()->after('mail_port');
            $table->string('mail_password')->nullable()->after('mail_username');
            $table->string('mail_encryption')->nullable()->default('tls')->after('mail_password');
            $table->string('mail_from_address')->nullable()->after('mail_encryption');
            $table->string('mail_from_name')->nullable()->default('SMAN 2 Tondano')->after('mail_from_address');
            $table->string('admin_email')->nullable()->after('recipient_target');
            $table->string('subject_hadir')->nullable()->after('admin_email');
            $table->string('subject_terlambat')->nullable()->after('subject_hadir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['email', 'parent_email']);
        });

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
                'admin_email',
                'subject_hadir',
                'subject_terlambat',
            ]);
        });
    }
};
