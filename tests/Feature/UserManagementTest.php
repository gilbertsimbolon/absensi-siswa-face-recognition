<?php

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

    $this->teacherUser = User::firstOrCreate(
        ['email' => 'guru_test@smandutdo.com'],
        ['name' => 'Guru Test, S.Pd', 'password' => bcrypt('password')]
    );
    if (! $this->teacherUser->hasRole('teacher')) {
        $this->teacherUser->assignRole('teacher');
    }

    $this->teacher = Teacher::firstOrCreate(
        ['user_id' => $this->teacherUser->id],
        ['nip' => '198801012015011001', 'phone' => '081234567890']
    );
});

test('admin can access manajemen user page and view user table', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

    $response->assertStatus(200);
    $response->assertViewIs('admin.manajemen-user.index');
    $response->assertSee('Manajemen User');
    $response->assertSee($this->teacherUser->name);
    $response->assertSee($this->teacherUser->email);
    $response->assertSee('Edit User');
    $response->assertSee('Konfirmasi Hapus User');
});

test('non-admin user cannot access manajemen user page', function () {
    $response = $this->actingAs($this->teacherUser)->get(route('admin.users.index'));

    $response->assertStatus(403);
});

test('admin can store new user', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Guru Baru, S.Kom',
        'email' => 'guru.baru@smandutdo.com',
        'password' => 'password123',
        'role' => 'teacher',
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'name' => 'Guru Baru, S.Kom',
        'email' => 'guru.baru@smandutdo.com',
    ]);

    $createdUser = User::where('email', 'guru.baru@smandutdo.com')->first();
    expect($createdUser->hasRole('teacher'))->toBeTrue();
});

test('admin can update user', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->teacherUser->id), [
        'name' => 'Guru Test Update, M.Pd',
        'email' => 'guru.updated@smandutdo.com',
        'role' => 'teacher',
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'id' => $this->teacherUser->id,
        'name' => 'Guru Test Update, M.Pd',
        'email' => 'guru.updated@smandutdo.com',
    ]);
});

test('admin cannot delete own account', function () {
    $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin->id));

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

test('admin can delete another user', function () {
    $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->teacherUser->id));

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('users', ['id' => $this->teacherUser->id]);
});
