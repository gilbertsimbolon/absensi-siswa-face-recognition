<?php

namespace Database\Seeders;

use App\Models\StudentFace;
use App\Models\Teacher;
use App\Models\TeacherFace;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sampleFace = StudentFace::first();
        $sampleFacePath = $sampleFace ? storage_path('app/public/'.$sampleFace->file_path) : null;

        $teachers = [
            [
                'name' => 'Drs. Jemmy Mandagi, M.Pd',
                'email' => 'jemmy.mandagi@smandutdo.com',
                'nip' => '196803151994031001',
                'phone' => '08114301001',
            ],
            [
                'name' => 'Meita Supit, S.Pd, M.Si',
                'email' => 'meita.supit@smandutdo.com',
                'nip' => '197205121998022002',
                'phone' => '08114301002',
            ],
            [
                'name' => 'Ferdinand Runtu, S.Pd',
                'email' => 'ferdinand.runtu@smandutdo.com',
                'nip' => '197508202002121003',
                'phone' => '08114301003',
            ],
            [
                'name' => 'Nancy Lumowa, S.Pd',
                'email' => 'nancy.lumowa@smandutdo.com',
                'nip' => '198001142005012004',
                'phone' => '08114301004',
            ],
            [
                'name' => 'Dr. Jerry Walandouw, M.Hum',
                'email' => 'jerry.walandouw@smandutdo.com',
                'nip' => '197411082000031005',
                'phone' => '08114301005',
            ],
            [
                'name' => 'Jenny Poluan, S.Kom',
                'email' => 'jenny.poluan@smandutdo.com',
                'nip' => '198506242009022006',
                'phone' => '08114301006',
            ],
            [
                'name' => 'Ronny Tendean, S.Pd',
                'email' => 'ronny.tendean@smandutdo.com',
                'nip' => '198209172008011007',
                'phone' => '08114301007',
            ],
            [
                'name' => 'Grace Mongi, S.Pd, M.Pd',
                'email' => 'grace.mongi@smandutdo.com',
                'nip' => '197904032006042008',
                'phone' => '08114301008',
            ],
            [
                'name' => 'Steven Wenas, S.Si',
                'email' => 'steven.wenas@smandutdo.com',
                'nip' => '198802192014021009',
                'phone' => '08114301009',
            ],
            [
                'name' => 'Debby Kawatu, S.Pd',
                'email' => 'debby.kawatu@smandutdo.com',
                'nip' => '199107302019032010',
                'phone' => '08114301010',
            ],
        ];

        foreach ($teachers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                ]
            );

            if (! $user->hasRole('teacher')) {
                $user->assignRole('teacher');
            }

            $teacher = Teacher::updateOrCreate(
                ['nip' => $data['nip']],
                [
                    'user_id' => $user->id,
                    'phone' => $data['phone'],
                ]
            );

            // Salin foto sampel jika tersedia
            if ($sampleFacePath && File::exists($sampleFacePath)) {
                $storageDir = 'teacher_faces/'.$teacher->nip;
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
                $pythonDir = base_path('python-service/dataset/'.$teacher->nip);
                if (! File::exists($pythonDir)) {
                    File::makeDirectory($pythonDir, 0755, true);
                }
                $pythonFilePath = $pythonDir.'/'.$fileName;
                if (! File::exists($pythonFilePath)) {
                    File::copy($sampleFacePath, $pythonFilePath);
                }

                // Buat record foto wajah guru
                TeacherFace::firstOrCreate(
                    [
                        'teacher_id' => $teacher->id,
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
