<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class PythonServiceManager
{
    /**
     * Dapatkan URL dasar layanan mikro Python dari konfigurasi.
     */
    public static function getBaseUrl(): string
    {
        return config('services.deepface.base_url', 'http://127.0.0.1:5000');
    }

    /**
     * Dapatkan target URL halaman pemindai Flask (melalui reverse proxy atau domain utama).
     */
    public static function getScannerUrl(): string
    {
        return config('services.deepface.scanner_url', '/scanner/');
    }

    /**
     * Dapatkan URL streaming MJPEG video kamera OpenCV.
     */
    public static function getStreamUrl(): string
    {
        $scannerUrl = self::getScannerUrl();
        if (str_starts_with($scannerUrl, 'http://') || str_starts_with($scannerUrl, 'https://')) {
            return rtrim($scannerUrl, '/').'/video_feed';
        }

        // Jika environment lokal tanpa reverse proxy Nginx (port 8000, 8080, dsb.)
        if (app()->environment('local') && in_array((int) request()->getPort(), [8000, 8080, 3000])) {
            return rtrim(self::getBaseUrl(), '/').'/video_feed';
        }

        return rtrim($scannerUrl, '/').'/video_feed';
    }

    /**
     * Dapatkan URL polling status pemindai OpenCV secara real-time.
     */
    public static function getStatusPollUrl(): string
    {
        $scannerUrl = self::getScannerUrl();
        if (str_starts_with($scannerUrl, 'http://') || str_starts_with($scannerUrl, 'https://')) {
            return rtrim($scannerUrl, '/').'/status-scanner';
        }

        if (app()->environment('local') && in_array((int) request()->getPort(), [8000, 8080, 3000])) {
            return rtrim(self::getBaseUrl(), '/').'/status-scanner';
        }

        return rtrim($scannerUrl, '/').'/status-scanner';
    }

    /**
     * Periksa apakah layanan Python saat ini sedang berjalan dan aktif.
     */
    public static function isRunning(): bool
    {
        $baseUrl = self::getBaseUrl();
        $parsed = parse_url($baseUrl);
        $host = $parsed['host'] ?? '127.0.0.1';
        $port = (int) ($parsed['port'] ?? 5000);

        // 1. Uji koneksi soket cepat (timeout 0.3 detik)
        $connection = @fsockopen($host, $port, $errorCode, $errorMessage, 0.3);
        if (! is_resource($connection)) {
            return false;
        }
        fclose($connection);

        // 2. Verifikasi endpoint status
        try {
            $response = Http::timeout(1)->get("{$baseUrl}/status");

            return $response->successful() && ($response->json('status') === 'aktif');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Periksa ketersediaan layanan Python tanpa menjalankan proses secara otomatis.
     * Sesuai ketentuan: proses Python harus dijalankan secara terpisah dari Laravel.
     *
     * @return array{running: bool, status: string, message: string}
     */
    public static function ensureRunning(): array
    {
        if (self::isRunning()) {
            return [
                'running' => true,
                'status' => 'online',
                'message' => 'Layanan Python OpenCV & FaceNet aktif dan siap digunakan.',
            ];
        }

        return [
            'running' => false,
            'status' => 'offline',
            'message' => 'Layanan pemindai wajah (Flask) sedang tidak aktif. Harap jalankan layanan Python secara terpisah di server.',
        ];
    }

    /**
     * Hasilkan token otentikasi terenkripsi HMAC untuk akses aman ke layanan Flask.
     * Memastikan hanya user Laravel yang sah (admin/guru) yang dapat mengakses scanner Flask.
     */
    public static function generateAuthToken(?User $user = null): string
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return '';
        }

        $userId = $user->id;
        $role = $user->roles->first()?->name ?? 'user';
        $expires = time() + (2 * 3600); // 2 jam masa berlaku
        $payload = base64_encode(json_encode([
            'uid' => $userId,
            'role' => $role,
            'exp' => $expires,
        ]));

        $key = config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $sig = hash_hmac('sha256', $payload, $key);

        return "{$payload}.{$sig}";
    }

    /**
     * Dapatkan status scanner dan deteksi terakhir dari layanan Python.
     *
     * @return array{online: bool, fps: int, camera_source: mixed, latest_result: ?array, message: string}
     */
    public static function getStatus(): array
    {
        if (! self::isRunning()) {
            return [
                'online' => false,
                'fps' => 0,
                'camera_source' => 0,
                'latest_result' => null,
                'message' => 'Layanan kamera OpenCV sedang tidak aktif.',
            ];
        }

        try {
            $baseUrl = self::getBaseUrl();
            $response = Http::timeout(1)->get("{$baseUrl}/status-scanner");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'online' => (bool) ($data['aktif'] ?? true),
                    'fps' => (int) ($data['fps'] ?? 0),
                    'camera_source' => $data['sumber'] ?? 0,
                    'latest_result' => $data['hasil_terakhir'] ?? null,
                    'message' => 'Kamera aktif',
                ];
            }
        } catch (\Throwable $e) {
            // Lewati jika timeout
        }

        return [
            'online' => false,
            'fps' => 0,
            'camera_source' => 0,
            'latest_result' => null,
            'message' => 'Gagal membaca status scanner kamera.',
        ];
    }

    /**
     * Ubah sumber kamera OpenCV (Webcam 0, iPhone USB 1, USB 2, atau URL IP Camera).
     *
     * @return array{status: string, pesan: string, sumber_sekarang: mixed}
     */
    public static function changeCamera(int|string $source): array
    {
        try {
            $baseUrl = self::getBaseUrl();
            $response = Http::timeout(4)->post("{$baseUrl}/ganti-kamera", [
                'sumber' => $source,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'error',
                'pesan' => 'Layanan Python merespons dengan kesalahan.',
                'sumber_sekarang' => $source,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'pesan' => 'Tidak dapat terhubung ke server kamera Python: '.$e->getMessage(),
                'sumber_sekarang' => $source,
            ];
        }
    }
}
