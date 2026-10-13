<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\NotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $batasAlpa = $studentClass?->batas_alpa
            ? Carbon::parse($studentClass->batas_alpa)->format('H:i')
            : '08:30';

        $placeholders = [
            '{nama_siswa}' => $student?->name ?? 'Siswa',
            '{nisn}' => $student?->nisn ?? '-',
            '{kelas}' => $studentClass?->name ?? '-',
            '{nama_ortu}' => ! empty($student?->parent_name) ? $student->parent_name : 'Bapak/Ibu Orang Tua / Wali',
            '{status}' => $statusText,
            '{waktu}' => $checkInTime,
            '{batas_terlambat}' => $batasTerlambat,
            '{batas_alpa}' => $batasAlpa,
            '{tanggal}' => $dateFormatted,
            '{sekolah}' => 'SMAN 2 Tondano',
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Mengirim pesan teks tunggal ke Gateway Baileys WA.
     *
     * @return array{status: string, pesan: string, response?: mixed}
     */
    public static function sendMessage(string $phone, string $message, ?NotificationSetting $setting = null): array
    {
        $setting = $setting ?? NotificationSetting::getSettings();
        $formattedPhone = self::formatNumber($phone);

        if (! $formattedPhone) {
            return [
                'status' => 'error',
                'pesan' => "Format nomor WhatsApp tidak valid: {$phone}",
            ];
        }

        $gatewayUrl = rtrim($setting->wa_gateway_url ?: 'http://127.0.0.1:3000', '/');

        try {
            $headers = ['Content-Type' => 'application/json'];
            if (! empty($setting->wa_api_key)) {
                $headers['Authorization'] = 'Bearer '.$setting->wa_api_key;
                $headers['X-API-KEY'] = $setting->wa_api_key;
            }

            $response = Http::timeout(6)
                ->withHeaders($headers)
                ->post("{$gatewayUrl}/send-message", [
                    'number' => $formattedPhone,
                    'phone' => $formattedPhone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'sukses',
                    'pesan' => "Pesan berhasil dikirim ke {$formattedPhone}",
                    'response' => $response->json(),
                ];
            }

            $errorMsg = $response->json('message') ?? $response->json('pesan') ?? $response->body();

            return [
                'status' => 'error',
                'pesan' => "Gateway WhatsApp mengembalikan status {$response->status()}: {$errorMsg}",
            ];
        } catch (\Exception $e) {
            Log::warning("Gagal menghubungi WhatsApp Gateway Baileys: {$e->getMessage()}");

            return [
                'status' => 'error',
                'pesan' => "Gagal terhubung ke WhatsApp Gateway di {$gatewayUrl}: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Mengirim notifikasi presensi otomatis berdasarkan aturan & template dinamis.
     *
     * @return array{status: string, pesan: string, detail?: array}
     */
    public static function sendAttendanceNotification(Attendance $attendance): array
    {
        $setting = NotificationSetting::getSettings();

        // 1. Cek apakah fitur aktif
        if (! $setting->is_active) {
            return [
                'status' => 'skipped',
                'pesan' => 'Notifikasi WhatsApp dinonaktifkan dalam pengaturan.',
            ];
        }

        // 2. Cek filter status kehadiran
        $status = $attendance->status;
        if ($status === Attendance::STATUS_HADIR && ! $setting->send_on_hadir) {
            return [
                'status' => 'skipped',
                'pesan' => 'Pengiriman untuk status HADIR dinonaktifkan.',
            ];
        }

        if ($status === Attendance::STATUS_TERLAMBAT && ! $setting->send_on_terlambat) {
            return [
                'status' => 'skipped',
                'pesan' => 'Pengiriman untuk status TERLAMBAT dinonaktifkan.',
            ];
        }

        // 3. Tentukan template pesan
        $template = ($status === Attendance::STATUS_TERLAMBAT)
            ? $setting->template_terlambat
            : $setting->template_hadir;

        $message = self::parseTemplate($template, $attendance);
        $student = $attendance->student;

        // 4. Kumpulkan daftar nomor tujuan berdasarkan konfigurasi penerima
        $targetNumbers = [];

        if (in_array($setting->recipient_target, ['parent', 'both']) && ! empty($student?->parent_phone)) {
            $targetNumbers[] = $student->parent_phone;
        }

        if (in_array($setting->recipient_target, ['student', 'both']) && ! empty($student?->phone)) {
            $targetNumbers[] = $student->phone;
        }

        // Jika nomor admin/sekolah disertakan
        if (! empty($setting->admin_phone)) {
            $targetNumbers[] = $setting->admin_phone;
        }

        $targetNumbers = array_unique(array_filter($targetNumbers));

        if (empty($targetNumbers)) {
            return [
                'status' => 'skipped',
                'pesan' => 'Tidak ada nomor WhatsApp yang terdaftar untuk penerima ini.',
            ];
        }

        // 5. Kirim pesan ke seluruh nomor target
        $results = [];
        foreach ($targetNumbers as $phone) {
            $results[$phone] = self::sendMessage($phone, $message, $setting);
        }

        return [
            'status' => 'sukses',
            'pesan' => 'Notifikasi WhatsApp berhasil diproses.',
            'detail' => $results,
        ];
    }

    /**
     * Mengecek status koneksi Gateway Baileys (Apakah terhubung & QR Code jika belum).
     *
     * @return array{online: bool, connected: bool, phone: ?string, qr: ?string, message: string}
     */
    public static function getGatewayStatus(?NotificationSetting $setting = null): array
    {
        $setting = $setting ?? NotificationSetting::getSettings();
        $gatewayUrl = rtrim($setting->wa_gateway_url ?: 'http://127.0.0.1:3000', '/');

        try {
            $response = Http::timeout(3)->get("{$gatewayUrl}/status");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'online' => true,
                    'connected' => (bool) ($data['connected'] ?? $data['is_connected'] ?? false),
                    'phone' => $data['user'] ?? $data['number'] ?? $data['phone'] ?? null,
                    'qr' => $data['qr'] ?? null,
                    'message' => $data['message'] ?? 'Gateway WhatsApp aktif.',
                ];
            }

            return [
                'online' => false,
                'connected' => false,
                'phone' => null,
                'qr' => null,
                'message' => "Gateway merespons dengan kode {$response->status()}",
            ];
        } catch (\Exception $e) {
            return [
                'online' => false,
                'connected' => false,
                'phone' => null,
                'qr' => null,
                'message' => "Tidak dapat menghubungi server Baileys di {$gatewayUrl}",
            ];
        }
    }
}
