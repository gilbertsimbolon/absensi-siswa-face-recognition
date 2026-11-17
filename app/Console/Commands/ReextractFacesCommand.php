<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentFace;
use App\Services\PythonServiceManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ReextractFacesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faces:reextract {nisn? : NISN siswa tertentu (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ekstrak ulang vektor embedding FaceNet untuk seluruh siswa terdaftar';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== RE-EKSTRAKSI EMBEDDING FACENET SISWA ===');

        if (! PythonServiceManager::isRunning()) {
            $this->info('[*] Menjalankan service Python...');
            PythonServiceManager::startService();
            sleep(2);
        }

        $specificNisn = $this->argument('nisn');
        $query = Student::with('faces');
        if ($specificNisn) {
            $query->where('nisn', $specificNisn);
        }

        $students = $query->get();
        if ($students->isEmpty()) {
            $this->warn('Tidak ada data siswa ditemukan.');

            return self::SUCCESS;
        }

        $this->info("Memproses {$students->count()} data siswa...");

        $successCount = 0;

        $candidateUrls = array_filter(array_unique([
            'http://127.0.0.1:5000',
            'http://localhost:5000',
            config('services.deepface.base_url', 'http://127.0.0.1:5000'),
        ]));

        $pythonBaseUrl = 'http://127.0.0.1:5000';
        foreach ($candidateUrls as $url) {
            try {
                $check = Http::timeout(2)->get("{$url}/status");
                if ($check->successful()) {
                    $pythonBaseUrl = rtrim($url, '/');
                    $this->info("[+] Terhubung ke Microservice Python di: {$pythonBaseUrl}");
                    break;
                }
            } catch (\Throwable $e) {
                // Coba kandidat berikutnya
            }
        }

        foreach ($students as $student) {
            $nisn = (string) $student->nisn;
            $this->line("-> Siswa: {$student->name} (NISN: {$nisn})");

            // Pastikan folder ada dan memiliki foto
            $studentFolder = storage_path("app/public/faces/{$nisn}");
            if (! File::exists($studentFolder)) {
                $this->comment("   [!] Folder foto tidak ditemukan: {$studentFolder}");

                continue;
            }

            // Daftarkan foto ke student_faces jika belum tercatat
            $files = File::files($studentFolder);
            foreach ($files as $file) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $relPath = "faces/{$nisn}/".$file->getFilename();
                    StudentFace::firstOrCreate([
                        'student_id' => $student->id,
                        'file_path' => $relPath,
                    ]);
                }
            }

            // Panggil Python untuk ekstraksi embedding FaceNet
            try {
                $response = Http::timeout(60)->post("{$pythonBaseUrl}/sinkronisasi-siswa/{$nisn}", [
                    'nama' => $student->name,
                ]);

                if ($response->successful()) {
                    $aiData = $response->json();
                    $embeddingsMap = $aiData['embeddings'] ?? [];

                    $saved = 0;
                    foreach ($embeddingsMap as $fn => $vector) {
                        $updated = StudentFace::where('student_id', $student->id)
                            ->where('file_path', 'like', "%{$fn}")
                            ->update(['embedding' => $vector]);
                        if ($updated) {
                            $saved++;
                        }
                    }

                    $this->info("   [+] Berhasil mengekstrak {$saved} embedding wajah.");
                    $successCount++;
                } else {
                    $this->error('   [x] Gagal dari microservice: '.$response->body());
                }
            } catch (\Throwable $e) {
                $this->error('   [x] Kesalahan koneksi ke Python: '.$e->getMessage());
            }
        }

        // Segarkan data di Python
        try {
            Http::timeout(5)->post("{$pythonBaseUrl}/segarkan-data");
            $this->info('[+] Service Python berhasil disegarkan dengan data terbaru.');
        } catch (\Throwable $e) {
            // Ignore
        }

        // Diagnostik keunikan vektor di antara seluruh siswa terdaftar
        $this->info('Memeriksa keunikan vektor di antara seluruh siswa terdaftar...');
        $faces = StudentFace::with('student')->whereNotNull('embedding')->get();
        $duplicatesFound = 0;

        for ($i = 0; $i < $faces->count(); $i++) {
            for ($j = $i + 1; $j < $faces->count(); $j++) {
                $f1 = $faces[$i];
                $f2 = $faces[$j];

                if ($f1->student_id === $f2->student_id) {
                    continue;
                }

                $v1 = $f1->embedding;
                $v2 = $f2->embedding;

                if (! is_array($v1) || ! is_array($v2) || count($v1) !== count($v2)) {
                    continue;
                }

                $dot = 0.0;
                $norm1 = 0.0;
                $norm2 = 0.0;
                for ($k = 0; $k < count($v1); $k++) {
                    $dot += $v1[$k] * $v2[$k];
                    $norm1 += $v1[$k] ** 2;
                    $norm2 += $v2[$k] ** 2;
                }

                $norm1 = sqrt($norm1) ?: 1.0;
                $norm2 = sqrt($norm2) ?: 1.0;
                $sim = $dot / ($norm1 * $norm2);
                $dist = 1.0 - $sim;

                if ($dist < 0.28) {
                    $s1Name = $f1->student?->name ?? 'Siswa';
                    $s1Nisn = $f1->student?->nisn ?? '-';
                    $s2Name = $f2->student?->name ?? 'Siswa';
                    $s2Nisn = $f2->student?->nisn ?? '-';

                    $this->warn("   [!] PERHATIAN (DUPLIKAT/KEMBAR): {$s1Name} ({$s1Nisn}) dan {$s2Name} ({$s2Nisn}) sangat mirip (Jarak: ".round($dist, 4).', Kemiripan: '.round($sim * 100, 1).'%). Pastikan berkas foto kedua siswa ini tidak sama.');
                    $duplicatesFound++;
                }
            }
        }

        if ($duplicatesFound === 0) {
            $this->info('[+] Seluruh vektor siswa 100% unik dan tidak ada bentrok/kembar!');
        }

        $this->info("=== SELESAI: {$successCount} siswa berhasil diekstrak ulang ===");

        return self::SUCCESS;
    }
}
