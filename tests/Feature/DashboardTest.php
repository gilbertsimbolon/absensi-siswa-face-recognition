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

    $this->admin = User::factory()->create(['name' => 'Admin Test']);
    $this->admin->assignRole('admin');

    $this->teacherUser = User::factory()->create(['name' => 'Guru Test']);
    $this->teacherUser->assignRole('teacher');
    $this->teacher = Teacher::factory()->create([
        'user_id' => $this->teacherUser->id,
    ]);
});

test('unauthenticated user cannot access dashboard', function () {
    $response = $this->get(route('admin.dashboard.index'));
    $response->assertRedirect(route('login'));
});

test('admin can access dashboard and view all wireframe components', function () {
    $academicYear = AcademicYear::factory()->create();
    $class = Classes::factory()->create([
        'name' => 'XII MIPA 1',
        'jam_masuk' => '07:00:00',
        'batas_terlambat' => '07:15:00',
    ]);
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'name' => 'Michael Siswa',
    ]);

    Attendance::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $academicYear->id,
        'date' => now()->format('Y-m-d'),
        'check_in_time' => '07:05:00',
        'status' => Attendance::STATUS_HADIR,
        'method' => Attendance::METHOD_FACE,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard.index'));

    $response->assertStatus(200);
    $response->assertViewIs('admin.beranda.index');

    // Cek komponen wireframe
    $response->assertSee('Admin Test');
    $response->assertSee('Jam Masuk');
    $response->assertSee('Batas Absensi');
    $response->assertSee('Jumlah Siswa');
    $response->assertSee('Jumlah Guru');
    $response->assertSee('Jumlah Kelas');
    $response->assertSee('Grafik Kehadiran');
    $response->assertSee('Persentase Kehadiran');
    $response->assertSee('Aktivitas Absensi');
    $response->assertSee('Michael Siswa');
});

test('teacher can access dashboard and see relevant data', function () {
    $response = $this->actingAs($this->teacherUser)->get(route('admin.dashboard.index'));

    $response->assertStatus(200);
    $response->assertSee('Guru Test');
    $response->assertSee('Jam Masuk');
    $response->assertSee('Batas Absensi');
});
