<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Pastikan layanan Python berjalan secara otomatis di latar belakang.
     * Mencegah proses ganda dengan atomic cache lock.
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

        // Kunci atomik selama 25 detik untuk mencegah peluncuran proses ganda
        $lock = Cache::lock('python_service_autolaunch', 25);

        if (! $lock->get()) {
            return [
                'running' => false,
                'status' => 'starting',
                'message' => 'Layanan Python sedang dalam proses inisialisasi di latar belakang. Silakan tunggu sejenak...',
            ];
        }

        try {
            self::launchProcess();

            // Berikan jeda singkat 1 detik lalu periksa apakah port sudah terbuka
            sleep(1);
            if (self::isRunning()) {
                return [
                    'running' => true,
                    'status' => 'online',
                    'message' => 'Layanan Python berhasil dinyalakan otomatis.',
                ];
            }

            return [
                'running' => false,
                'status' => 'starting',
                'message' => 'Layanan Python sedang dinyalakan otomatis (memuat model AI Facenet). Halaman akan menyegarkan otomatis...',
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal menjalankan layanan Python otomatis: '.$e->getMessage());

            return [
                'running' => false,
                'status' => 'error',
                'message' => 'Gagal menyalakan layanan Python: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Tentukan executable binary python yang valid di sistem.
     */
    private static function resolvePythonBinary(): string
    {
        $configured = config('services.deepface.python_binary');
        if (! empty($configured) && $configured !== 'python' && file_exists($configured)) {
            return $configured;
        }

        // Deteksi path python terpasang di Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $localAppData = getenv('LOCALAPPDATA') ?: '';
            $candidates = [
                $localAppData.'\\Programs\\Python\\Python311\\python.exe',
                $localAppData.'\\Programs\\Python\\Python312\\python.exe',
                $localAppData.'\\Programs\\Python\\Python310\\python.exe',
                'C:\\Python311\\python.exe',
                'C:\\Python312\\python.exe',
            ];

            foreach ($candidates as $candidate) {
                if ($candidate && file_exists($candidate)) {
                    return $candidate;
                }
            }
        }

        return 'python';
    }

    /**
     * Luncurkan proses Python di latar belakang sesuai lingkungan sistem operasi.
     */
    private static function launchProcess(): void
    {
        $pythonBinary = self::resolvePythonBinary();
        $pythonDir = base_path('python-service');
        $logFile = storage_path('logs/python-service.log');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Lingkungan Windows: Jalankan proses terpisah tanpa blocking menggunakan PowerShell Start-Process
            $cmd = sprintf(
                'powershell -WindowStyle Hidden -Command "Start-Process \'%s\' -ArgumentList \'main.py\' -WorkingDirectory \'%s\' -WindowStyle Hidden"',
                $pythonBinary,
                $pythonDir
            );
            @exec($cmd);
        } else {
            // Lingkungan Linux/Unix
            $cmd = sprintf(
                'cd %s && nohup %s main.py > %s 2>&1 &',
                escapeshellarg($pythonDir),
                escapeshellarg($pythonBinary),
                escapeshellarg($logFile)
            );
            @exec($cmd);
        }
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
        self::ensureRunning();

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
