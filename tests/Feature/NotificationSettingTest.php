<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\NotificationSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can view notification settings page', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.notification'));

    $response->assertStatus(200);
    $response->assertViewIs('admin.pengaturan.notifikasi');
    $response->assertSee('Pengaturan Notifikasi WhatsApp');
    $response->assertSee('Template Teks Dinamis');
});

test('admin can update notification settings', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.settings.notification.update'), [
        'wa_gateway_url' => 'http://127.0.0.1:3000',
        'is_active' => '1',
        'send_on_hadir' => '1',
        'send_on_terlambat' => '1',
        'recipient_target' => 'parent',
        'admin_phone' => '081234567890',
        'template_hadir' => 'Halo {nama_ortu}, {nama_siswa} hadir pukul {waktu}.',
        'template_terlambat' => 'Halo {nama_ortu}, {nama_siswa} terlambat.',
    ]);

    $response->assertRedirect(route('admin.settings.notification'));
    $response->assertSessionHas('success');

    $setting = NotificationSetting::first();
    expect($setting->is_active)->toBeTrue()
        ->and($setting->recipient_target)->toBe('parent')
        ->and($setting->admin_phone)->toBe('081234567890')
        ->and($setting->template_hadir)->toContain('{nama_siswa}');
});

test('whatsapp service formats phone number correctly', function () {
    expect(WhatsAppService::formatNumber('081234567890'))->toBe('6281234567890')
        ->and(WhatsAppService::formatNumber('+62 812-3456-7890'))->toBe('6281234567890')
        ->and(WhatsAppService::formatNumber('81234567890'))->toBe('6281234567890')
        ->and(WhatsAppService::formatNumber('123'))->toBeNull();
});

test('whatsapp service parses dynamic placeholders correctly', function () {
    $academicYear = AcademicYear::factory()->create();
    $class = Classes::factory()->create(['name' => 'XII MIPA 1']);
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'name' => 'Josep Gilbert',
        'nisn' => '0025625634',
        'parent_name' => 'Bpk. Simbolon',
    ]);

    $attendance = Attendance::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-10-13',
        'check_in_time' => '07:10:00',
        'status' => Attendance::STATUS_HADIR,
        'method' => Attendance::METHOD_FACE,
    ]);

    $template = 'Yth {nama_ortu}, ananda {nama_siswa} ({kelas}, NISN: {nisn}) berstatus {status} pada {waktu}. Sekolah: {sekolah}.';
    $parsed = WhatsAppService::parseTemplate($template, $attendance);

    expect($parsed)->toContain('Bpk. Simbolon')
        ->toContain('Josep Gilbert')
        ->toContain('XII MIPA 1')
        ->toContain('0025625634')
        ->toContain('HADIR')
        ->toContain('07:10')
        ->toContain('SMAN 2 Tondano');
});

test('whatsapp service skips notification when inactive', function () {
    $setting = NotificationSetting::getSettings();
    $setting->update(['is_active' => false]);

    $academicYear = AcademicYear::factory()->create();
    $class = Classes::factory()->create();
    $student = Student::factory()->create(['class_id' => $class->id]);

    $attendance = Attendance::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-10-13',
        'status' => Attendance::STATUS_HADIR,
        'method' => Attendance::METHOD_FACE,
    ]);

    $result = WhatsAppService::sendAttendanceNotification($attendance);

    expect($result['status'])->toBe('skipped');
});

test('admin can fetch gateway status json', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.notification.status'));

    $response->assertStatus(200);
    $response->assertJsonStructure(['online', 'connected', 'message']);
});
