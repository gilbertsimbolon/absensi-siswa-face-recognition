<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = AcademicYear::firstOrCreate(
            ['name' => '2025/2026', 'semester' => 'Ganjil'],
            ['is_active' => true]
        );

        AcademicYear::firstOrCreate(
            ['name' => '2025/2026', 'semester' => 'Genap'],
            ['is_active' => false]
        );

        AcademicYear::firstOrCreate(
            ['name' => '2026/2027', 'semester' => 'Ganjil'],
            ['is_active' => false]
        );

        // Catat histori untuk siswa yang sudah memiliki kelas aktif
        $students = Student::whereNotNull('class_id')->get();
        foreach ($students as $student) {
            StudentClassHistory::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $currentYear->id,
                ],
                [
                    'class_id' => $student->class_id,
                    'status' => 'aktif',
                ]
            );
        }
    }
}
