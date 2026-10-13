<?php

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\Classes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->teacherUser = User::factory()->create();
    $this->teacherUser->assignRole('teacher');

    $this->academicYear = AcademicYear::create([
        'name' => '2025/2026',
        'semester' => 'Ganjil',
        'is_active' => true,
    ]);
});

test('admin can view konfigurasi page', function () {
    Classes::factory()->create(['name' => 'X-1', 'grade_level' => 'X']);

    $response = $this->actingAs($this->admin)->get(route('admin.settings.konfigurasi'));

    $response->assertStatus(200);
    $response->assertViewIs('admin.master-data.konfigurasi');
    $response->assertSee('Konfigurasi');
    $response->assertSee('Akurasi Pemindaian Wajah');
    $response->assertSee('Minimum Akurasi');
    $response->assertSee('X-1');
});

test('admin can update minimum face accuracy', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.settings.konfigurasi.accuracy'), [
        'min_face_accuracy' => 85.50,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $setting = AppSetting::getSettings();
    expect($setting->min_face_accuracy)->toBe(85.50);
});

test('minimum face accuracy validation rejects invalid inputs', function () {
    // Kurang dari batas min (30%)
    $response = $this->actingAs($this->admin)->post(route('admin.settings.konfigurasi.accuracy'), [
        'min_face_accuracy' => 20,
    ]);
    $response->assertSessionHasErrors(['min_face_accuracy']);

    // Lebih dari batas max (99.99%)
    $response = $this->actingAs($this->admin)->post(route('admin.settings.konfigurasi.accuracy'), [
        'min_face_accuracy' => 105,
    ]);
    $response->assertSessionHasErrors(['min_face_accuracy']);

    // Input bukan angka
    $response = $this->actingAs($this->admin)->post(route('admin.settings.konfigurasi.accuracy'), [
        'min_face_accuracy' => 'abc',
    ]);
    $response->assertSessionHasErrors(['min_face_accuracy']);
});

test('api endpoint returns configuration for face scanner', function () {
    AppSetting::getSettings()->update([
        'min_face_accuracy' => 78.50,
    ]);

    $response = $this->getJson(route('api.presensi.konfigurasi'));

    $response->assertStatus(200);
    $response->assertJson([
        'min_face_accuracy' => 78.50,
    ]);
});

test('non admin cannot access or update configuration', function () {
    $response = $this->actingAs($this->teacherUser)->get(route('admin.settings.konfigurasi'));
    $response->assertStatus(403);

    $responseUpdate = $this->actingAs($this->teacherUser)->post(route('admin.settings.konfigurasi.accuracy'), [
        'min_face_accuracy' => 80.00,
    ]);
    $responseUpdate->assertStatus(403);
});
