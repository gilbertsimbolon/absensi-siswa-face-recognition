<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->academicYear = AcademicYear::create([
        'name' => '2025/2026',
        'semester' => 'Ganjil',
        'is_active' => true,
    ]);
});

test('admin can view jam kelas page', function () {
    Classes::factory()->create(['name' => 'X-1', 'grade_level' => 'X']);

    $response = $this->actingAs($this->admin)->get(route('admin.settings.jam-kelas'));

    $response->assertStatus(200);
    $response->assertViewIs('admin.master-data.konfigurasi');
    $response->assertSee('Konfigurasi');
    $response->assertSee('X-1');
});

test('admin can update individual class schedule', function () {
    $class = Classes::factory()->create([
        'name' => 'X-1',
        'grade_level' => 'X',
        'jam_masuk' => '07:00:00',
        'batas_terlambat' => '07:15:00',
        'batas_alpa' => '08:30:00',
    ]);

    $response = $this->actingAs($this->admin)->put(route('admin.settings.jam-kelas.update', $class), [
        'jam_masuk' => '07:10',
        'batas_terlambat' => '07:25',
        'batas_alpa' => '08:45',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $class->refresh();
    expect($class->jam_masuk)->toBe('07:10:00')
        ->and($class->batas_terlambat)->toBe('07:25:00')
        ->and($class->batas_alpa)->toBe('08:45:00');
});

test('admin can update all class schedules simultaneously', function () {
    $class1 = Classes::factory()->create(['name' => 'X-1', 'grade_level' => 'X']);
    $class2 = Classes::factory()->create(['name' => 'XI-1', 'grade_level' => 'XI']);

    $response = $this->actingAs($this->admin)->post(route('admin.settings.jam-kelas.update-all'), [
        'jam_masuk' => '06:45',
        'batas_terlambat' => '07:05',
        'batas_alpa' => '08:15',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($class1->fresh()->batas_terlambat)->toBe('07:05:00')
        ->and($class2->fresh()->batas_terlambat)->toBe('07:05:00')
        ->and($class2->fresh()->batas_alpa)->toBe('08:15:00');
});

test('attendance marks hadir when scanning before or at batas terlambat', function () {
    $class = Classes::factory()->create([
        'name' => 'X-1',
        'jam_masuk' => '07:00:00',
        'batas_terlambat' => '07:15:00',
        'batas_alpa' => '08:30:00',
    ]);

    $student = Student::factory()->create([
        'class_id' => $class->id,
        'nisn' => '1122334455',
        'status' => 'aktif',
    ]);

    // Set waktu saat jam 07:10 WITA (sebelum batas terlambat 07:15)
    Carbon::setTestNow(Carbon::today('Asia/Makassar')->setTime(7, 10, 0));

    $response = $this->actingAs($this->admin)->postJson(route('admin.attendance.proses-pindai'), [
        'nisn' => '1122334455',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'sukses',
        'tipe' => 'masuk',
    ]);

    $attendance = Attendance::where('student_id', $student->id)->first();
    expect($attendance->status)->toBe(Attendance::STATUS_HADIR);

    Carbon::setTestNow();
});

test('attendance marks terlambat when scanning between batas terlambat and batas alpa', function () {
    $class = Classes::factory()->create([
        'name' => 'X-1',
        'jam_masuk' => '07:00:00',
        'batas_terlambat' => '07:15:00',
        'batas_alpa' => '08:30:00',
    ]);

    $student = Student::factory()->create([
        'class_id' => $class->id,
        'nisn' => '1122334466',
        'status' => 'aktif',
    ]);

    // Set waktu saat jam 07:45 WITA (setelah batas terlambat 07:15, sebelum batas alpa 08:30)
    Carbon::setTestNow(Carbon::today('Asia/Makassar')->setTime(7, 45, 0));

    $response = $this->actingAs($this->admin)->postJson(route('admin.attendance.proses-pindai'), [
        'nisn' => '1122334466',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('siswa.status', 'Terlambat');

    $attendance = Attendance::where('student_id', $student->id)->first();
    expect($attendance->status)->toBe(Attendance::STATUS_TERLAMBAT);

    Carbon::setTestNow();
});

test('attendance marks alpa when scanning after batas alpa', function () {
    $class = Classes::factory()->create([
        'name' => 'X-1',
        'jam_masuk' => '07:00:00',
        'batas_terlambat' => '07:15:00',
        'batas_alpa' => '08:30:00',
    ]);

    $student = Student::factory()->create([
        'class_id' => $class->id,
        'nisn' => '1122334477',
        'status' => 'aktif',
    ]);

    // Set waktu saat jam 09:15 WITA (setelah batas alpa 08:30)
    Carbon::setTestNow(Carbon::today('Asia/Makassar')->setTime(9, 15, 0));

    $response = $this->actingAs($this->admin)->postJson(route('admin.attendance.proses-pindai'), [
        'nisn' => '1122334477',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('siswa.status', 'Diluar Waktu Absen');

    $attendance = Attendance::where('student_id', $student->id)->first();
    expect($attendance->status)->toBe(Attendance::STATUS_ALPA);

    // Scan ulang kedua kali di hari yang sama menghasilkan status 'Sudah Absen'
    $responseKedua = $this->actingAs($this->admin)->postJson(route('admin.attendance.proses-pindai'), [
        'nisn' => '1122334477',
    ]);
    $responseKedua->assertStatus(200);
    $responseKedua->assertJsonPath('siswa.status', 'Sudah Absen');

    Carbon::setTestNow();
});
