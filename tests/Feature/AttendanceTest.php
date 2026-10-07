<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

    $this->admin = User::firstOrCreate(
        ['email' => 'admin_test@smandutdo.com'],
        ['name' => 'Admin Test', 'password' => bcrypt('password')]
    );
    if (! $this->admin->hasRole('admin')) {
        $this->admin->assignRole('admin');
    }

    $this->activeYear = AcademicYear::firstOrCreate(
        ['name' => '2025/2026', 'semester' => 'Ganjil'],
        ['is_active' => true]
    );
});

test('admin can access daily attendance page and see students', function () {
    $class = Classes::factory()->create(['name' => 'X-A', 'grade_level' => 'X']);
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'name' => 'Budi Santoso',
        'nisn' => '1234567890',
        'status' => 'aktif',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.attendance.index', ['class_id' => $class->id]));

    $response->assertStatus(200);
    $response->assertViewIs('admin.attendance.index');
    $response->assertSee('Absensi Harian');
    $response->assertSee('Budi Santoso');
    $response->assertSee('1234567890');
});

test('admin can access monthly attendance recapitulation page and see matrix', function () {
    $class = Classes::factory()->create(['name' => 'XI-IPA 1', 'grade_level' => 'XI']);
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'name' => 'Siti Nurhaliza',
        'status' => 'aktif',
    ]);

    Attendance::factory()->create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $this->activeYear->id,
        'date' => now()->format('Y-m-d'),
        'status' => Attendance::STATUS_HADIR,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.attendance.recap', [
        'class_id' => $class->id,
        'month' => now()->month,
        'year' => now()->year,
    ]));

    $response->assertStatus(200);
    $response->assertViewIs('admin.attendance.recap');
    $response->assertSee('Rekapitulasi Kehadiran');
    $response->assertSee('Siti Nurhaliza');
});

test('admin can access print recap page', function () {
    $class = Classes::factory()->create(['name' => 'XII-IPA 1', 'grade_level' => 'XII']);
    Student::factory()->create([
        'class_id' => $class->id,
        'name' => 'Ahmad Dahlan',
        'status' => 'aktif',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.attendance.print', [
        'class_id' => $class->id,
        'month' => now()->month,
        'year' => now()->year,
    ]));

    $response->assertStatus(200);
    $response->assertViewIs('admin.attendance.print-recap');
    $response->assertSee('REKAPITULASI KEHADIRAN SISWA');
    $response->assertSee('Ahmad Dahlan');
});

test('admin can record manual attendance for a student', function () {
    $class = Classes::factory()->create(['name' => 'X-B', 'grade_level' => 'X']);
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'status' => 'aktif',
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.attendance.store'), [
        'student_id' => $student->id,
        'date' => now()->format('Y-m-d'),
        'status' => Attendance::STATUS_SAKIT,
        'notes' => 'Demam tinggi',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Berhasil');

    $this->assertDatabaseHas('attendances', [
        'student_id' => $student->id,
        'status' => 'sakit',
        'notes' => 'Demam tinggi',
    ]);
});

test('admin can bulk mark unrecorded students as alpa', function () {
    $class = Classes::factory()->create(['name' => 'X-C', 'grade_level' => 'X']);
    $student1 = Student::factory()->create(['class_id' => $class->id, 'status' => 'aktif']);
    $student2 = Student::factory()->create(['class_id' => $class->id, 'status' => 'aktif']);

    // student 1 already has attendance
    Attendance::factory()->create([
        'student_id' => $student1->id,
        'class_id' => $class->id,
        'academic_year_id' => $this->activeYear->id,
        'date' => now()->format('Y-m-d'),
        'status' => Attendance::STATUS_HADIR,
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.attendance.bulk'), [
        'class_id' => $class->id,
        'date' => now()->format('Y-m-d'),
        'target_status' => 'alpa',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Berhasil');

    // student 2 should now be marked as alpa
    $this->assertDatabaseHas('attendances', [
        'student_id' => $student2->id,
        'status' => 'alpa',
    ]);

    // student 1 remains hadir
    $this->assertDatabaseHas('attendances', [
        'student_id' => $student1->id,
        'status' => 'hadir',
    ]);
});

test('teacher can only see their own class in attendance and recap', function () {
    $teacherUser = User::factory()->create(['email' => 'guru@smandutdo.com']);
    $teacherUser->assignRole('teacher');
    $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

    $myClass = Classes::factory()->create(['name' => 'Kelas Guru', 'teacher_id' => $teacher->id]);
    $otherClass = Classes::factory()->create(['name' => 'Kelas Lain', 'teacher_id' => null]);

    $response = $this->actingAs($teacherUser)->get(route('admin.attendance.recap'));

    $response->assertStatus(200);
    $response->assertSee('Kelas Guru');
    $response->assertDontSee('Kelas Lain');
});
