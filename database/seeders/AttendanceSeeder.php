<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classes;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        if (! $activeYear) {
            return;
        }

        $classes = Classes::with(['students' => function ($q) {
            $q->where('status', 'aktif');
        }])->get();

        $today = now();
        // Generate attendance for the last 10 school days up to today
        $dates = [];
        $cursor = $today->copy();
        while (count($dates) < 10) {
            if (! $cursor->isWeekend()) {
                $dates[] = $cursor->copy()->format('Y-m-d');
            }
            $cursor->subDay();
        }

        foreach ($classes as $class) {
            foreach ($class->students as $student) {
                foreach ($dates as $date) {
                    // Cek jika sudah ada
                    $exists = Attendance::where('student_id', $student->id)
                        ->where('date', $date)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $rand = rand(1, 100);
                    if ($rand <= 80) {
                        $minute = str_pad((string) rand(40, 59), 2, '0', STR_PAD_LEFT);
                        Attendance::create([
                            'student_id' => $student->id,
                            'class_id' => $class->id,
                            'academic_year_id' => $activeYear->id,
                            'date' => $date,
                            'check_in_time' => "06:{$minute}:00",
                            'check_out_time' => '14:05:00',
                            'status' => Attendance::STATUS_HADIR,
                            'method' => Attendance::METHOD_FACE,
                            'confidence_score' => rand(920, 995) / 10,
                            'notes' => null,
                        ]);
                    } elseif ($rand <= 90) {
                        $minute = str_pad((string) rand(20, 45), 2, '0', STR_PAD_LEFT);
                        Attendance::create([
                            'student_id' => $student->id,
                            'class_id' => $class->id,
                            'academic_year_id' => $activeYear->id,
                            'date' => $date,
                            'check_in_time' => "07:{$minute}:00",
                            'check_out_time' => '14:00:00',
                            'status' => Attendance::STATUS_TERLAMBAT,
                            'method' => Attendance::METHOD_FACE,
                            'confidence_score' => rand(910, 980) / 10,
                            'notes' => 'Terlambat masuk sekolah',
                        ]);
                    } elseif ($rand <= 95) {
                        Attendance::create([
                            'student_id' => $student->id,
                            'class_id' => $class->id,
                            'academic_year_id' => $activeYear->id,
                            'date' => $date,
                            'check_in_time' => null,
                            'check_out_time' => null,
                            'status' => Attendance::STATUS_SAKIT,
                            'method' => Attendance::METHOD_MANUAL,
                            'notes' => 'Sakit demam',
                        ]);
                    } elseif ($rand <= 98) {
                        Attendance::create([
                            'student_id' => $student->id,
                            'class_id' => $class->id,
                            'academic_year_id' => $activeYear->id,
                            'date' => $date,
                            'check_in_time' => null,
                            'check_out_time' => null,
                            'status' => Attendance::STATUS_IZIN,
                            'method' => Attendance::METHOD_MANUAL,
                            'notes' => 'Izin keperluan keluarga',
                        ]);
                    } else {
                        Attendance::create([
                            'student_id' => $student->id,
                            'class_id' => $class->id,
                            'academic_year_id' => $activeYear->id,
                            'date' => $date,
                            'check_in_time' => null,
                            'check_out_time' => null,
                            'status' => Attendance::STATUS_ALPA,
                            'method' => Attendance::METHOD_MANUAL,
                            'notes' => 'Tanpa keterangan',
                        ]);
                    }
                }
            }
        }
    }
}
