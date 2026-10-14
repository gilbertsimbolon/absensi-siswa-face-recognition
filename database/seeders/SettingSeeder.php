<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\NotificationSetting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pengaturan Aplikasi (Akurasi Wajah default)
        AppSetting::updateOrCreate(
            ['id' => 1],
            [
                'min_face_accuracy' => 70.00,
            ]
        );

        // 2. Pengaturan Notifikasi Email & SMTP
        NotificationSetting::updateOrCreate(
            ['id' => 1],
            [
                'is_active' => true,
                'send_on_hadir' => true,
                'send_on_terlambat' => true,
                'send_on_alpa' => true,
                'recipient_target' => 'parent',
                'mail_host' => config('mail.mailers.smtp.host') ?: env('MAIL_HOST', 'smtp.gmail.com'),
                'mail_port' => (int) (config('mail.mailers.smtp.port') ?: env('MAIL_PORT', 587)),
                'mail_username' => config('mail.mailers.smtp.username') ?: env('MAIL_USERNAME', 'gilbertttsimbolon@gmail.com'),
                'mail_password' => config('mail.mailers.smtp.password') ?: env('MAIL_PASSWORD'),
                'mail_encryption' => config('mail.mailers.smtp.encryption') ?: env('MAIL_ENCRYPTION', 'tls'),
                'mail_from_address' => config('mail.from.address') ?: env('MAIL_FROM_ADDRESS', 'gilbertttsimbolon@gmail.com'),
                'mail_from_name' => config('mail.from.name') ?: env('MAIL_FROM_NAME', 'SMAN 2 Tondano'),
                'admin_email' => 'gilbertttsimbolon@gmail.com',
                'subject_hadir' => 'Presensi Masuk: {nama_siswa} ({kelas}) - SMAN 2 Tondano',
                'subject_terlambat' => 'Pemberitahuan Keterlambatan: {nama_siswa} ({kelas}) - SMAN 2 Tondano',
                'template_hadir' => "Yth. Bapak/Ibu {nama_ortu},\n\nDiberitahukan bahwa ananda *{nama_siswa}* (Kelas: {kelas}, NISN: {nisn}) telah hadir di SMAN 2 Tondano.\n\nStatus: {status}\nWaktu: {waktu} WITA\nTanggal: {tanggal}\n\nTerima kasih atas perhatian dan kerja samanya.",
                'template_terlambat' => "Yth. Bapak/Ibu {nama_ortu},\n\nDiberitahukan bahwa ananda *{nama_siswa}* (Kelas: {kelas}, NISN: {nisn}) tercatat *TERLAMBAT* saat hadir di SMAN 2 Tondano.\n\nStatus: {status}\nWaktu: {waktu} WITA (Batas Terlambat: {batas_terlambat} WITA)\nTanggal: {tanggal}\n\nMohon perhatian dan pendampingan Bapak/Ibu. Terima kasih.",
            ]
        );
    }
}
