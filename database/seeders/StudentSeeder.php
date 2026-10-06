<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\Student;
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
        $class = Classes::firstOrCreate(
            ['name' => 'XII MIPA 1'],
            ['grade_level' => 'XII']
        );
        $classId = $class->id;

        // Cari file sampel foto wajah jika ada
        $sampleFacePath = null;
        $fallbackCandidates = [
            base_path('python-service/dataset/0025625452/1791218169_photo_depan.jpg'),
            base_path('python-service/dataset/0025625453/1791269549_photo_depan.jpg'),
            storage_path('app/public/faces/0025625453/1791269549_photo_depan.jpg'),
        ];
        foreach ($fallbackCandidates as $candidate) {
            if (File::exists($candidate)) {
                $sampleFacePath = $candidate;
                break;
            }
        }

        $students = [
            [
                'name' => 'Ciko',
                'nisn' => '0025625452',
                'class_id' => $classId,
                'gender' => 'L',
                'phone' => '085399684844',
                'parent_name' => 'Royke',
                'parent_phone' => '085399684844',
            ],
            [
                'name' => 'Bryan Wewengkang',
                'nisn' => '0025625453',
                'class_id' => $classId,
                'gender' => 'L',
                'phone' => '081245678901',
                'parent_name' => 'Royke Wewengkang',
                'parent_phone' => '081245678902',
            ],
            [
                'name' => 'Jessica Lumempouw',
                'nisn' => '0025625454',
                'class_id' => $classId,
                'gender' => 'P',
                'phone' => '082198765431',
                'parent_name' => 'Meiske Lumempouw',
                'parent_phone' => '082198765432',
            ],
            [
                'name' => 'Kevin Supit',
                'nisn' => '0025625455',
                'class_id' => $classId,
                'gender' => 'L',
                'phone' => '085233445561',
                'parent_name' => 'Hengky Supit',
                'parent_phone' => '085233445562',
            ],
            [
                'name' => 'Brenda Runtuwene',
                'nisn' => '0025625456',
                'class_id' => $classId,
                'gender' => 'P',
                'phone' => '089677889901',
                'parent_name' => 'Ferry Runtuwene',
                'parent_phone' => '089677889902',
            ],
            [
                'name' => 'Christian Palit',
                'nisn' => '0025625457',
                'class_id' => $classId,
                'gender' => 'L',
                'phone' => '081322334451',
                'parent_name' => 'Stevy Palit',
                'parent_phone' => '081322334452',
            ],
            [
                'name' => 'Gabriella Karwur',
                'nisn' => '0025625458',
                'class_id' => $classId,
                'gender' => 'P',
                'phone' => '085711223341',
                'parent_name' => 'Novi Karwur',
                'parent_phone' => '085711223342',
            ],
            [
                'name' => 'Michael Polii',
                'nisn' => '0025625459',
                'class_id' => $classId,
                'gender' => 'L',
                'phone' => '082255667781',
                'parent_name' => 'Johan Polii',
                'parent_phone' => '082255667782',
            ],
            [
                'name' => 'Natasha Wowor',
                'nisn' => '0025625460',
                'class_id' => $classId,
                'gender' => 'P',
                'phone' => '087844556671',
                'parent_name' => 'Franky Wowor',
                'parent_phone' => '087844556672',
            ],
            [
                'name' => 'Daniel Pangemanan',
                'nisn' => '0025625461',
                'class_id' => $classId,
                'gender' => 'L',
                'phone' => '081288990011',
                'parent_name' => 'Benny Pangemanan',
                'parent_phone' => '081288990012',
            ],
            [
                'name' => 'Priscilla Sumampouw',
                'nisn' => '0025625462',
                'class_id' => $classId,
                'gender' => 'P',
                'phone' => '085366778891',
                'parent_name' => 'Denny Sumampouw',
                'parent_phone' => '085366778892',
            ],
        ];

        foreach ($students as $studentData) {
            $student = Student::updateOrCreate(
                ['nisn' => $studentData['nisn']],
                $studentData
            );

            // Cek foto yang sudah ada untuk siswa ini di storage atau dataset
            $storageDir = 'faces/'.$student->nisn;
            $existingStorageFiles = Storage::disk('public')->exists($storageDir)
                ? Storage::disk('public')->files($storageDir)
                : [];

            $pythonDir = base_path('python-service/dataset/'.$student->nisn);
            $existingPythonFiles = File::exists($pythonDir)
                ? File::files($pythonDir)
                : [];

            $relativePhotoPath = null;

            if (! empty($existingStorageFiles)) {
                $relativePhotoPath = $existingStorageFiles[0];
            } elseif (! empty($existingPythonFiles)) {
                $sourceFile = $existingPythonFiles[0]->getRealPath();
                $fileName = $existingPythonFiles[0]->getFilename();
                $relativePhotoPath = $storageDir.'/'.$fileName;
                Storage::disk('public')->makeDirectory($storageDir);
                File::copy($sourceFile, storage_path('app/public/'.$relativePhotoPath));
            } elseif ($sampleFacePath && File::exists($sampleFacePath)) {
                $fileName = time().'_photo_depan.jpg';
                $relativePhotoPath = $storageDir.'/'.$fileName;
                Storage::disk('public')->makeDirectory($storageDir);
                File::copy($sampleFacePath, storage_path('app/public/'.$relativePhotoPath));

                if (! File::exists($pythonDir)) {
                    File::makeDirectory($pythonDir, 0755, true);
                }
                File::copy($sampleFacePath, $pythonDir.'/'.$fileName);
            }

            if ($relativePhotoPath) {
                // Pastikan juga tersalin ke python-service dataset jika belum ada
                $fileName = basename($relativePhotoPath);
                $pythonFilePath = $pythonDir.'/'.$fileName;
                if (! File::exists($pythonFilePath) && File::exists(storage_path('app/public/'.$relativePhotoPath))) {
                    if (! File::exists($pythonDir)) {
                        File::makeDirectory($pythonDir, 0755, true);
                    }
                    File::copy(storage_path('app/public/'.$relativePhotoPath), $pythonFilePath);
                }

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
