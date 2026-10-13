<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'is_active',
        'send_on_hadir',
        'send_on_terlambat',
        'send_on_alpa',
        'recipient_target',
        'admin_email',
        'subject_hadir',
        'subject_terlambat',
        'template_hadir',
        'template_terlambat',
        'wa_gateway_url',
        'wa_api_key',
        'admin_phone',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_on_hadir' => 'boolean',
        'send_on_terlambat' => 'boolean',
        'send_on_alpa' => 'boolean',
        'mail_port' => 'integer',
    ];

    /**
     * Mengambil instance konfigurasi notifikasi (singleton pattern).
     */
    public static function getSettings(): self
    {
        $setting = self::first();

        if (! $setting) {
            $setting = self::create([
                'is_active' => false,
                'send_on_hadir' => true,
                'send_on_terlambat' => true,
                'recipient_target' => 'parent',
                'mail_host' => null,
                'mail_port' => 587,
                'mail_username' => null,
                'mail_password' => null,
                'mail_encryption' => 'tls',
                'mail_from_address' => 'presensi@sman2tondano.sch.id',
                'mail_from_name' => 'Presensi SMAN 2 Tondano',
                'admin_email' => null,
                'subject_hadir' => 'Presensi Masuk: {nama_siswa} ({kelas}) - SMAN 2 Tondano',
                'subject_terlambat' => 'Pemberitahuan Keterlambatan: {nama_siswa} ({kelas}) - SMAN 2 Tondano',
                'template_hadir' => "Yth. Bapak/Ibu {nama_ortu},\n\nDiberitahukan bahwa ananda *{nama_siswa}* (Kelas: {kelas}, NISN: {nisn}) telah hadir di SMAN 2 Tondano.\n\nStatus: {status}\nWaktu: {waktu} WITA\nTanggal: {tanggal}\n\nTerima kasih atas perhatian dan kerja samanya.",
                'template_terlambat' => "Yth. Bapak/Ibu {nama_ortu},\n\nDiberitahukan bahwa ananda *{nama_siswa}* (Kelas: {kelas}, NISN: {nisn}) tercatat *TERLAMBAT* saat hadir di SMAN 2 Tondano.\n\nStatus: {status}\nWaktu: {waktu} WITA (Batas Terlambat: {batas_terlambat} WITA)\nTanggal: {tanggal}\n\nMohon perhatian dan pendampingan Bapak/Ibu. Terima kasih.",
            ]);
        }

        // Pastikan default subject terisi jika ada record lama
        if (empty($setting->subject_hadir)) {
            $setting->update([
                'subject_hadir' => 'Presensi Masuk: {nama_siswa} ({kelas}) - SMAN 2 Tondano',
                'subject_terlambat' => 'Pemberitahuan Keterlambatan: {nama_siswa} ({kelas}) - SMAN 2 Tondano',
            ]);
        }

        return $setting;
    }
}
