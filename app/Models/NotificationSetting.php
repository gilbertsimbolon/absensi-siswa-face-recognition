<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'wa_gateway_url',
        'wa_api_key',
        'is_active',
        'send_on_hadir',
        'send_on_terlambat',
        'recipient_target',
        'admin_phone',
        'template_hadir',
        'template_terlambat',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_on_hadir' => 'boolean',
        'send_on_terlambat' => 'boolean',
    ];

    /**
     * Mengambil instance konfigurasi notifikasi (singleton pattern).
     */
    public static function getSettings(): self
    {
        $setting = self::first();

        if (! $setting) {
            $setting = self::create([
                'wa_gateway_url' => 'http://127.0.0.1:3000',
                'wa_api_key' => null,
                'is_active' => false,
                'send_on_hadir' => true,
                'send_on_terlambat' => true,
                'recipient_target' => 'parent',
                'admin_phone' => null,
                'template_hadir' => "Yth. Bapak/Ibu {nama_ortu},\n\nDiberitahukan bahwa ananda *{nama_siswa}* (Kelas {kelas}, NISN: {nisn}) telah hadir di *SMAN 2 Tondano*.\n\nStatus: *{status}*\nWaktu: {waktu} WITA\nTanggal: {tanggal}\n\nTerima kasih atas perhatian Bapak/Ibu.",
                'template_terlambat' => "Yth. Bapak/Ibu {nama_ortu},\n\nDiberitahukan bahwa ananda *{nama_siswa}* (Kelas {kelas}, NISN: {nisn}) tercatat *TERLAMBAT* saat hadir di *SMAN 2 Tondano*.\n\nStatus: *{status}*\nWaktu: {waktu} WITA (Batas: 07:15 WITA)\nTanggal: {tanggal}\n\nMohon perhatian dan pendampingan Bapak/Ibu. Terima kasih.",
            ]);
        }

        return $setting;
    }
}
