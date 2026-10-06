<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentFace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ambil tahun ajaran aktif
        $activeYear = AcademicYear::getActive();
        if (! $activeYear) {
            $activeYear = AcademicYear::firstOrCreate(
                ['name' => '2025/2026', 'semester' => 'Ganjil'],
                ['is_active' => true]
            );
        }

        // 2. Ambil 21 kelas yang sudah diurutkan hierarkis
        $classes = Classes::orderByRaw("CASE
                WHEN grade_level = 'X' THEN 1
                WHEN grade_level = 'XI' THEN 2
                WHEN grade_level = 'XII' THEN 3
                ELSE 4 END")
            ->orderByRaw("CASE
                WHEN name LIKE '%MIPA%' THEN 1
                WHEN name LIKE '%IPS%' THEN 2
                ELSE 3 END")
            ->orderBy('name')
            ->get();

        if ($classes->isEmpty()) {
            $this->call(ClassSeeder::class);
            $classes = Classes::all();
        }

        // 3. Sumber foto sampel
        $sampleFacePath = null;
        $fallbackCandidates = [
            base_path('python-service/dataset/0025625452/1791218169_photo_depan.jpg'),
            base_path('python-service/dataset/0025625453/1791269549_photo_depan.jpg'),
            storage_path('app/public/faces/0025625452/1791218169_photo_depan.jpg'),
            storage_path('app/public/faces/0025625453/1791269549_photo_depan.jpg'),
        ];
        foreach ($fallbackCandidates as $candidate) {
            if (File::exists($candidate)) {
                $sampleFacePath = $candidate;
                break;
            }
        }

        // 4. Data 11 siswa bawaan (historis)
        $initialStudents = [
            [
                'name' => 'Ciko',
                'nisn' => '0025625452',
                'gender' => 'L',
                'phone' => '085399684844',
                'parent_name' => 'Royke Mandagi',
                'parent_phone' => '085399684844',
            ],
            [
                'name' => 'Bryan Wewengkang',
                'nisn' => '0025625453',
                'gender' => 'L',
                'phone' => '081245678901',
                'parent_name' => 'Royke Wewengkang',
                'parent_phone' => '081245678902',
            ],
            [
                'name' => 'Jessica Lumempouw',
                'nisn' => '0025625454',
                'gender' => 'P',
                'phone' => '082198765431',
                'parent_name' => 'Meiske Lumempouw',
                'parent_phone' => '082198765432',
            ],
            [
                'name' => 'Kevin Supit',
                'nisn' => '0025625455',
                'gender' => 'L',
                'phone' => '085233445561',
                'parent_name' => 'Hengky Supit',
                'parent_phone' => '085233445562',
            ],
            [
                'name' => 'Brenda Runtuwene',
                'nisn' => '0025625456',
                'gender' => 'P',
                'phone' => '089677889901',
                'parent_name' => 'Ferry Runtuwene',
                'parent_phone' => '089677889902',
            ],
            [
                'name' => 'Christian Palit',
                'nisn' => '0025625457',
                'gender' => 'L',
                'phone' => '081322334451',
                'parent_name' => 'Stevy Palit',
                'parent_phone' => '081322334452',
            ],
            [
                'name' => 'Gabriella Karwur',
                'nisn' => '0025625458',
                'gender' => 'P',
                'phone' => '085711223341',
                'parent_name' => 'Novi Karwur',
                'parent_phone' => '085711223342',
            ],
            [
                'name' => 'Michael Polii',
                'nisn' => '0025625459',
                'gender' => 'L',
                'phone' => '082255667781',
                'parent_name' => 'Johan Polii',
                'parent_phone' => '082255667782',
            ],
            [
                'name' => 'Natasha Wowor',
                'nisn' => '0025625460',
                'gender' => 'P',
                'phone' => '087844556671',
                'parent_name' => 'Franky Wowor',
                'parent_phone' => '087844556672',
            ],
            [
                'name' => 'Daniel Pangemanan',
                'nisn' => '0025625461',
                'gender' => 'L',
                'phone' => '081288990011',
                'parent_name' => 'Benny Pangemanan',
                'parent_phone' => '081288990012',
            ],
            [
                'name' => 'Priscilla Sumampouw',
                'nisn' => '0025625462',
                'gender' => 'P',
                'phone' => '085366778891',
                'parent_name' => 'Denny Sumampouw',
                'parent_phone' => '085366778892',
            ],
        ];

        // 5. Bank data nama khas Minahasa & Indonesia untuk SMAN 2 Tondano
        $maleFirstNames = [
            'Christian', 'Kevin', 'Bryan', 'Daniel', 'Michael', 'Jonathan', 'Samuel', 'Matthew', 'Gabriel', 'Brandon',
            'Dave', 'Joshua', 'Andrew', 'Nathan', 'Jason', 'Rafael', 'Timothy', 'Jeremy', 'David', 'Steven',
            'Ryan', 'Alden', 'Geraldo', 'Juan', 'Richard', 'Arthur', 'Billy', 'Dennis', 'Ray', 'Aldo',
            'Glen', 'Marsel', 'Marvel', 'Junior', 'Ezra', 'Kenzo', 'Lionel', 'Darren', 'Justin', 'Alex',
            'Adrian', 'Franco', 'Gilbert', 'William', 'Rizky', 'Rangga', 'Alvin', 'Nicholas', 'Mario', 'Andre',
        ];

        $femaleFirstNames = [
            'Jessica', 'Brenda', 'Gabriella', 'Natasha', 'Priscilla', 'Aurel', 'Kezia', 'Michelle', 'Stevany', 'Cindy',
            'Vanessa', 'Karen', 'Angel', 'Patricia', 'Felicia', 'Gladys', 'Chelsea', 'Clarissa', 'Sharon', 'Gracia',
            'Febby', 'Gita', 'Nadine', 'Olivia', 'Laura', 'Bella', 'Irene', 'Maria', 'Ester', 'Debora',
            'Rachel', 'Tiara', 'Regina', 'Valerie', 'Cantika', 'Alicia', 'Christin', 'Vania', 'Fiona', 'Kayla',
            'Nathania', 'Cynthia', 'Grace', 'Verena', 'Jennifer', 'Tasya', 'Audrey', 'Stefani', 'Valeska', 'Clara',
        ];

        $lastNames = [
            'Wewengkang', 'Lumempouw', 'Mandagi', 'Supit', 'Runtu', 'Palit', 'Karwur', 'Polii', 'Wowor', 'Pangemanan',
            'Sumampouw', 'Paat', 'Tombokan', 'Lontoh', 'Tendean', 'Mongi', 'Wenas', 'Kawatu', 'Rondonuwu', 'Rompas',
            'Sanger', 'Warouw', 'Taroreh', 'Manopo', 'Dotulong', 'Parengkuan', 'Sondakh', 'Kumendong', 'Walangitan', 'Rori',
            'Tumengkol', 'Posumah', 'Lapian', 'Lengkey', 'Kolondam', 'Moniaga', 'Rattu', 'Manueke', 'Rogahang', 'Kaunang',
            'Mogot', 'Tulung', 'Sumual', 'Rumagit', 'Pangkerego', 'Kerap', 'Turang', 'Watuseke', 'Kojongian', 'Manoppo',
            'Mamuaya', 'Liando', 'Maramis', 'Kalesaran', 'Roring', 'Kandou', 'Sumayku', 'Lumentut', 'Pantouw', 'Waworuntu',
        ];

        $parentFirstNames = [
            'Royke', 'Ferry', 'Hengky', 'Stevy', 'Johan', 'Franky', 'Benny', 'Denny', 'Frits', 'Jantje',
            'Welly', 'Meidy', 'Lucky', 'Sonny', 'Donny', 'Nofri', 'Ronny', 'Jemmy', 'Maxie', 'Hendy',
            'Robby', 'Teddy', 'Eddy', 'Boy', 'Nixon', 'Tommy', 'Dave', 'Karel', 'Willem', 'Markus',
        ];

        // 6. Siapkan 210 siswa (21 kelas x 10 siswa)
        $allStudentPayloads = [];

        // Masukkan 11 siswa bawaan ke daftar
        foreach ($initialStudents as $init) {
            $allStudentPayloads[] = $init;
        }

        // Lengkapi sisa siswa hingga mencapai 210 siswa
        $currentNisnNumber = 5463;
        $nameIndex = 0;

        while (count($allStudentPayloads) < 210) {
            $isMale = (count($allStudentPayloads) % 2 === 0);
            $firstName = $isMale
                ? $maleFirstNames[$nameIndex % count($maleFirstNames)]
                : $femaleFirstNames[$nameIndex % count($femaleFirstNames)];
            $lastName = $lastNames[$nameIndex % count($lastNames)];
            $parentFirst = $parentFirstNames[$nameIndex % count($parentFirstNames)];

            $nameIndex++;
            $nisn = sprintf('002562%04d', $currentNisnNumber++);
            $idx = count($allStudentPayloads) + 1;

            $phoneSeq = str_pad((string) ($idx + 10), 4, '0', STR_PAD_LEFT);
            $parentPhoneSeq = str_pad((string) ($idx + 50), 4, '0', STR_PAD_LEFT);

            $allStudentPayloads[] = [
                'name' => "{$firstName} {$lastName}",
                'nisn' => $nisn,
                'gender' => $isMale ? 'L' : 'P',
                'phone' => "081244{$phoneSeq}1",
                'parent_name' => "{$parentFirst} {$lastName}",
                'parent_phone' => "081355{$parentPhoneSeq}2",
            ];
        }

        // 7. Distribusikan tepat 10 siswa ke masing-masing kelas dari 21 kelas
        // Jika ada siswa bawaan (10 siswa pertama) di XII MIPA 1, tetap posisikan XII MIPA 1 mendapat 10 siswa pertama
        $classCount = $classes->count();
        $payloadIndex = 0;

        foreach ($classes as $class) {
            for ($slot = 1; $slot <= 10; $slot++) {
                if ($payloadIndex >= count($allStudentPayloads)) {
                    break;
                }

                $data = $allStudentPayloads[$payloadIndex];
                $payloadIndex++;

                // Simpan atau perbarui data siswa
                $student = Student::updateOrCreate(
                    ['nisn' => $data['nisn']],
                    [
                        'name' => $data['name'],
                        'class_id' => $class->id,
                        'gender' => $data['gender'],
                        'phone' => $data['phone'],
                        'parent_name' => $data['parent_name'],
                        'parent_phone' => $data['parent_phone'],
                        'status' => 'aktif',
                    ]
                );

                // 8. Catat histori kelas siswa pada tahun ajaran aktif
                StudentClassHistory::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $activeYear->id,
                    ],
                    [
                        'class_id' => $class->id,
                        'status' => 'aktif',
                    ]
                );

                // 9. Sinkronisasi foto wajah siswa jika foto sampel tersedia
                $storageDir = 'faces/'.$student->nisn;
                $hasStorageFace = Storage::disk('public')->exists($storageDir)
                    && ! empty(Storage::disk('public')->files($storageDir));

                $relativePhotoPath = null;

                if ($hasStorageFace) {
                    $files = Storage::disk('public')->files($storageDir);
                    $relativePhotoPath = $files[0];
                } elseif ($sampleFacePath && File::exists($sampleFacePath)) {
                    $fileName = 'photo_depan.jpg';
                    $relativePhotoPath = $storageDir.'/'.$fileName;

                    if (! Storage::disk('public')->exists($storageDir)) {
                        Storage::disk('public')->makeDirectory($storageDir);
                    }

                    $destPath = storage_path('app/public/'.$relativePhotoPath);
                    if (! File::exists($destPath)) {
                        File::copy($sampleFacePath, $destPath);
                    }

                    // Sinkronisasi ke python dataset jika ada direktori python-service
                    $pythonDir = base_path('python-service/dataset/'.$student->nisn);
                    if (! File::exists($pythonDir)) {
                        File::makeDirectory($pythonDir, 0755, true);
                    }
                    $pythonFilePath = $pythonDir.'/'.$fileName;
                    if (! File::exists($pythonFilePath)) {
                        File::copy($sampleFacePath, $pythonFilePath);
                    }
                }

                if ($relativePhotoPath) {
                    StudentFace::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'label' => 'Tampak Depan',
                        ],
                        [
                            'file_path' => $relativePhotoPath,
                        ]
                    );
                }
            }
        }
    }
}
