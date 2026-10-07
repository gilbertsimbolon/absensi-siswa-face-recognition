<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'class_id' => Classes::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'date' => fake()->date(),
            'check_in_time' => '07:05:00',
            'check_out_time' => '14:00:00',
            'status' => Attendance::STATUS_HADIR,
            'method' => Attendance::METHOD_FACE,
            'confidence_score' => fake()->randomFloat(2, 90, 99),
            'snapshot_path' => null,
            'notes' => null,
        ];
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Attendance::STATUS_TERLAMBAT,
            'check_in_time' => '07:35:00',
        ]);
    }

    public function sick(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Attendance::STATUS_SAKIT,
            'check_in_time' => null,
            'check_out_time' => null,
            'notes' => 'Surat dokter terlampir',
        ]);
    }

    public function permission(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Attendance::STATUS_IZIN,
            'check_in_time' => null,
            'check_out_time' => null,
            'notes' => 'Izin keperluan keluarga',
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Attendance::STATUS_ALPA,
            'check_in_time' => null,
            'check_out_time' => null,
            'notes' => 'Tanpa keterangan',
        ]);
    }
}
