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
        $pythonBaseUrl = config('services.deepface.base_url', 'http://127.0.0.1:5000');

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

        $this->info("=== SELESAI: {$successCount} siswa berhasil diekstrak ulang ===");

        return self::SUCCESS;
    }
}
