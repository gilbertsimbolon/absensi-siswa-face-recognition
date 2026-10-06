<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = ['X', 'XI', 'XII'];
        $streams = [
            'MIPA' => 5,
            'IPS' => 2,
        ];

        // 1. Pastikan semua 21 kelas sudah ada
        foreach ($levels as $level) {
            foreach ($streams as $stream => $max) {
                for ($i = 1; $i <= $max; $i++) {
                    $name = "{$level} {$stream} {$i}";
                    Classes::firstOrCreate(
                        ['name' => $name],
                        ['grade_level' => $level]
                    );
                }
            }
        }

        // 2. Ambil 21 kelas berurutan hierarkis
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

        // 3. Ambil seluruh guru (21 guru)
        $teachers = Teacher::orderBy('id')->get();

        // 4. Hubungkan masing-masing 1 wali kelas secara unik (1-to-1)
        foreach ($classes as $index => $class) {
            $teacher = $teachers[$index % $teachers->count()] ?? null;
            if ($teacher) {
                $class->update(['teacher_id' => $teacher->id]);
            }
        }
    }
}
