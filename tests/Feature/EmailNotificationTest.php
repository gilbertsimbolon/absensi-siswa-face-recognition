<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Student;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('email notification service generates static email for hadir, terlambat, and alpa correctly', function () {
    $academicYear = AcademicYear::factory()->create();
    $class = Classes::factory()->create([
        'name' => 'XII MIPA 1',
        'batas_terlambat' => '07:15:00',
        'batas_alpa' => '08:30:00',
    ]);
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'name' => 'Josep Gilbert',
        'nisn' => '0025625634',
        'parent_name' => 'Bpk. Simbolon',
        'email' => 'gilbert@student.sch.id',
        'parent_email' => 'simbolon@parent.com',
    ]);

    // 1. Hadir
    $attendanceHadir = Attendance::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-10-13',
        'check_in_time' => '07:10:00',
        'status' => Attendance::STATUS_HADIR,
        'method' => Attendance::METHOD_FACE,
    ]);
    $emailHadir = EmailNotificationService::generateStaticEmail($attendanceHadir);
    expect($emailHadir['subject'])->toContain('Josep Gilbert')
        ->toContain('Hadir Tepat Waktu')
        ->and($emailHadir['html'])->toContain('HADIR')
        ->toContain('0025625634')
        ->toContain('XII MIPA 1');

    // 2. Terlambat
    $attendanceTerlambat = Attendance::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-10-14',
        'check_in_time' => '07:35:00',
        'status' => Attendance::STATUS_TERLAMBAT,
        'method' => Attendance::METHOD_FACE,
    ]);
    $emailTerlambat = EmailNotificationService::generateStaticEmail($attendanceTerlambat);
    expect($emailTerlambat['subject'])->toContain('Josep Gilbert')
        ->toContain('Terlambat')
        ->and($emailTerlambat['html'])->toContain('TERLAMBAT')
        ->toContain('melewati batas toleransi keterlambatan');

    // 3. Alpa
    $attendanceAlpa = Attendance::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-10-15',
        'check_in_time' => '09:00:00',
        'status' => Attendance::STATUS_ALPA,
        'method' => Attendance::METHOD_FACE,
    ]);
    $emailAlpa = EmailNotificationService::generateStaticEmail($attendanceAlpa);
    expect($emailAlpa['subject'])->toContain('Josep Gilbert')
        ->toContain('Alpa')
        ->and($emailAlpa['html'])->toContain('ALPA')
        ->toContain('melewati batas toleransi alpa');
});

test('email notification service sends email to student and parent when active', function () {
    Mail::fake();

    $academicYear = AcademicYear::factory()->create();
    $class = Classes::factory()->create(['name' => 'XI-1']);
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'name' => 'Budi Santoso',
        'email' => 'budi@test.com',
        'parent_email' => 'ortu.budi@test.com',
    ]);

    $attendance = Attendance::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-10-13',
        'check_in_time' => '07:05:00',
        'status' => Attendance::STATUS_HADIR,
        'method' => Attendance::METHOD_FACE,
    ]);

    $result = EmailNotificationService::sendAttendanceNotification($attendance);

    expect($result['status'])->toBe('sukses')
        ->and($result['detail'])->toHaveKeys(['ortu.budi@test.com', 'budi@test.com']);
});

test('email notification service skips notification when no emails are registered', function () {
    $academicYear = AcademicYear::factory()->create();
    $class = Classes::factory()->create();
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'email' => null,
        'parent_email' => null,
    ]);

    $attendance = Attendance::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-10-13',
        'status' => Attendance::STATUS_HADIR,
        'method' => Attendance::METHOD_FACE,
    ]);

    $result = EmailNotificationService::sendAttendanceNotification($attendance);

    expect($result['status'])->toBe('skipped');
});

test('email notification service skips when notification is disabled in config', function () {
    Config::set('mail.notification_enabled', false);

    $academicYear = AcademicYear::factory()->create();
    $class = Classes::factory()->create();
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'email' => 'test@test.com',
    ]);

    $attendance = Attendance::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-10-13',
        'status' => Attendance::STATUS_HADIR,
        'method' => Attendance::METHOD_FACE,
    ]);

    $result = EmailNotificationService::sendAttendanceNotification($attendance);

    expect($result['status'])->toBe('skipped');
});

test('sendTestEmail sends email successfully', function () {
    Mail::fake();

    $result = EmailNotificationService::sendTestEmail('admin@sman2tondano.sch.id');

    expect($result['status'])->toBe('sukses');
});
