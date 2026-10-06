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
        $class = Classes::first();
        $classId = $class ? $class->id : 1;

        $sampleFace = StudentFace::first();
        $sampleFacePath = $sampleFace ? storage_path('app/public/'.$sampleFace->file_path) : null;

        $students = [
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

            // Salin foto sampel jika tersedia
            if ($sampleFacePath && File::exists($sampleFacePath)) {
                $storageDir = 'faces/'.$student->nisn;
                $fileName = time().'_photo_depan.jpg';
                $relativePath = $storageDir.'/'.$fileName;

                if (! Storage::disk('public')->exists($storageDir)) {
                    Storage::disk('public')->makeDirectory($storageDir);
                }

                $destPath = storage_path('app/public/'.$relativePath);
                if (! File::exists($destPath)) {
                    File::copy($sampleFacePath, $destPath);
                }

                // Salin ke dataset python-service
                $pythonDir = base_path('python-service/dataset/'.$student->nisn);
                if (! File::exists($pythonDir)) {
                    File::makeDirectory($pythonDir, 0755, true);
                }
                $pythonFilePath = $pythonDir.'/'.$fileName;
                if (! File::exists($pythonFilePath)) {
                    File::copy($sampleFacePath, $pythonFilePath);
                }

                // Buat record foto wajah siswa
                StudentFace::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'label' => 'Tampak Depan',
                    ],
                    [
                        'file_path' => $relativePath,
                    ]
                );
            }
        }
    }
}
