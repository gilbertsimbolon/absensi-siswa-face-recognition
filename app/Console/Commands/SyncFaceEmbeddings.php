<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

#[Signature('app:sync-face-embeddings')]
#[Description('Sinkronisasi vektor embedding FaceNet 128-dimensi ke tabel student_faces')]
class SyncFaceEmbeddings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai sinkronisasi vektor embedding FaceNet ke database...');

        $embeddingsData = [];

        // 1. Coba ambil dari file embeddings.json jika tersedia
        $jsonPath = base_path('python-service/embeddings.json');
        if (File::exists($jsonPath)) {
            $embeddingsData = json_decode(File::get($jsonPath), true) ?: [];
        }

        // 2. Jika kosong, coba ambil dari microservice Python
        if (empty($embeddingsData)) {
            try {
                $response = Http::timeout(10)->get('http://127.0.0.1:5000/scanner/semua-embedding');
                if ($response->successful()) {
                    $embeddingsData = $response->json('data') ?: [];
                }
            } catch (\Throwable $e) {
                $this->warn('Microservice Python tidak merespons: ' . $e->getMessage());
            }
        }

        if (empty($embeddingsData)) {
            $this->error('Tidak ada data embedding yang dapat disinkronkan.');

            return self::FAILURE;
        }

        $totalSynced = 0;
        $students = Student::with('faces')->get();

        foreach ($students as $student) {
            $nisn = (string) $student->nisn;
            if (! isset($embeddingsData[$nisn])) {
                continue;
            }

            $studentEmbeddings = $embeddingsData[$nisn];
            $faces = $student->faces;

            foreach ($faces as $idx => $face) {
                // Cari matching berdasarkan filename
                $matchedEmb = null;
                $currentFilename = basename($face->file_path);

                foreach ($studentEmbeddings as $embItem) {
                    if (basename($embItem['filename'] ?? '') === $currentFilename) {
                        $matchedEmb = $embItem['embedding'] ?? null;
                        break;
                    }
                }

                // Fallback ke urutan indeks jika tidak match by nama file
                if (! $matchedEmb && isset($studentEmbeddings[$idx])) {
                    $matchedEmb = $studentEmbeddings[$idx]['embedding'] ?? null;
                }

                if ($matchedEmb) {
                    $face->update(['embedding' => $matchedEmb]);
                    $totalSynced++;
                }
            }
        }

        $this->info("Berhasil menyinkronkan {$totalSynced} vektor embedding 128-D ke tabel student_faces!");

        return self::SUCCESS;
    }
}
