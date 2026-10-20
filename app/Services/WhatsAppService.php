<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\NotificationSetting;
use Carbon\Carbon;

class WhatsAppService
{
    /**
     * Memformat nomor telepon ke format internasional WhatsApp (628xxx).
     */
    public static function formatNumber(?string $number): ?string
    {
        if (! $number) {
            return null;
        }

        // Hapus semua karakter selain angka
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        if (empty($cleaned)) {
            return null;
        }

        // Jika diawali 0 (contoh: 0812...), ganti 0 dengan 62
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62'.substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62'.$cleaned;
        }

        // Validasi panjang nomor wajar (minimal 10 digit, maksimal 16 digit)
        if (strlen($cleaned) < 10 || strlen($cleaned) > 16) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Mengganti variabel placeholder dinamis pada template pesan.
     */
    public static function parseTemplate(string $template, Attendance $attendance): string
    {
        $student = $attendance->student;

        $checkInTime = $attendance->check_in_time
            ? Carbon::parse($attendance->check_in_time)->format('H:i')
            : Carbon::now()->format('H:i');

        $dateFormatted = $attendance->date
            ? Carbon::parse($attendance->date)->translatedFormat('d F Y')
            : Carbon::today()->translatedFormat('d F Y');

        $statusText = match ($attendance->status) {
            Attendance::STATUS_TERLAMBAT => 'TERLAMBAT',
            Attendance::STATUS_HADIR => 'HADIR',
            Attendance::STATUS_IZIN => 'IZIN',
            Attendance::STATUS_SAKIT => 'SAKIT',
            Attendance::STATUS_ALPHA => 'ALPA',
            default => strtoupper((string) $attendance->status),
        };

        $studentClass = $student?->classes;
        $batasTerlambat = $studentClass?->batas_terlambat
            ? Carbon::parse($studentClass->batas_terlambat)->format('H:i')
            : '07:15';

        $placeholders = [
            '{nama_siswa}' => $student?->name ?? 'Siswa',
            '{nisn}' => $student?->nisn ?? '-',
            '{kelas}' => $studentClass?->name ?? '-',
            '{nama_ortu}' => ! empty($student?->parent_name) ? $student->parent_name : 'Bapak/Ibu Orang Tua / Wali',
            '{status}' => $statusText,
            '{waktu}' => $checkInTime,
            '{batas_terlambat}' => $batasTerlambat,
            '{batas_alpa}' => $batasTerlambat,
            '{tanggal}' => $dateFormatted,
            '{sekolah}' => 'SMAN 2 Tondano',
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Mengirim pesan teks tunggal ke Gateway Baileys WA.
     * Force bypass: Layanan WhatsApp di-force nonaktif karena kolom nomor HP telah ditiadakan.
     *
     * @return array{status: string, pesan: string, response?: mixed}
     */
    public static function sendMessage(string $phone, string $message, ?NotificationSetting $setting = null): array
    {
        return [
            'status' => 'skipped',
            'pesan' => 'Layanan WhatsApp di-force nonaktif (sistem menggunakan notifikasi email).',
        ];
    }

    /**
     * Mengirim notifikasi presensi otomatis berdasarkan aturan & template dinamis.
     * Force bypass: Layanan WhatsApp di-force nonaktif.
     *
     * @return array{status: string, pesan: string, detail?: array}
     */
    public static function sendAttendanceNotification(Attendance $attendance): array
    {
        return [
            'status' => 'skipped',
            'pesan' => 'Layanan WhatsApp di-force nonaktif (kolom nomor HP ditiadakan dari sistem).',
        ];
    }

    /**
     * Mengecek status koneksi Gateway Baileys (Apakah terhubung & QR Code jika belum).
     *
     * @return array{online: bool, connected: bool, phone: ?string, qr: ?string, message: string}
     */
    public static function getGatewayStatus(?NotificationSetting $setting = null): array
    {
        return [
            'online' => false,
            'connected' => false,
            'phone' => null,
            'qr' => null,
            'message' => 'Layanan WhatsApp di-force nonaktif.',
        ];
    }
}
