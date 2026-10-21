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
     * Jalankan layanan mikro Python secara otomatis jika sedang tidak aktif.
     */
    public static function startService(): bool
    {
        if (self::isRunning()) {
            return true;
        }

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $scriptPath = base_path('python-service/aplikasi.py');
                $workDir = base_path('python-service');
                pclose(popen("start /B \"Python AI\" python \"{$scriptPath}\"", 'r'));
            } else {
                // Di Linux / VPS
                $venvPython = base_path('python-service/venv/bin/python');
                $scriptPath = base_path('python-service/aplikasi.py');

                if (file_exists('/etc/systemd/system/absensi-ai.service')) {
                    @exec('sudo systemctl start absensi-ai > /dev/null 2>&1 &');
                } elseif (file_exists($venvPython)) {
                    @exec('cd '.base_path('python-service')." && {$venvPython} {$scriptPath} > /dev/null 2>&1 &");
                } else {
                    @exec('cd '.base_path('python-service')." && python3 {$scriptPath} > /dev/null 2>&1 &");
                }
            }

            // Beri toleransi waktu hingga 2 detik agar proses mengikat port 5000
            for ($i = 0; $i < 8; $i++) {
                usleep(250000);
                if (self::isRunning()) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return self::isRunning();
    }

    /**
     * Periksa ketersediaan layanan Python, dan jalankan otomatis jika offline.
     *
     * @return array{running: bool, status: string, message: string}
     */
    public static function ensureRunning(): array
    {
        if (self::isRunning() || self::startService()) {
            return [
                'running' => true,
                'status' => 'online',
                'message' => 'Layanan Python OpenCV & FaceNet aktif dan siap digunakan.',
            ];
        }

        return [
            'running' => false,
            'status' => 'offline',
            'message' => 'Layanan pemindai wajah (Flask) sedang tidak aktif. Harap jalankan layanan Python di server.',
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
     * Dapatkan status ketersediaan layanan AI DeepFace Python.
     *
     * @return array{online: bool, fps: int, camera_source: mixed, latest_result: ?array, message: string}
     */
    public static function getStatus(): array
    {
        $running = self::isRunning();

        return [
            'online' => $running,
            'fps' => 0,
            'camera_source' => 'client',
            'latest_result' => null,
            'message' => $running ? 'Layanan AI DeepFace aktif dan siap.' : 'Layanan AI DeepFace sedang tidak aktif.',
        ];
    }

    /**
     * Ganti sumber kamera (kini dikelola langsung oleh klien browser).
     *
     * @return array{status: string, pesan: string, sumber_sekarang: mixed}
     */
    public static function changeCamera(int|string $source): array
    {
        return [
            'status' => 'sukses',
            'pesan' => 'Kamera dikelola langsung oleh peramban klien (HTML5 WebRTC).',
            'sumber_sekarang' => $source,
        ];
    }
}
