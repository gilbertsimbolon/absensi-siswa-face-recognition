<?php

use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Pastikan role admin tersedia
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::firstOrCreate(
        ['email' => 'admin_test@smandutdo.com'],
        ['name' => 'Admin Test', 'password' => bcrypt('password')]
    );
    if (! $this->admin->hasRole('admin')) {
        $this->admin->assignRole('admin');
    }

    // Pastikan tahun ajaran aktif ada
    $this->activeYear = AcademicYear::firstOrCreate(
        ['name' => '2025/2026', 'semester' => 'Ganjil'],
        ['is_active' => true]
    );

    $this->targetYear = AcademicYear::firstOrCreate(
        ['name' => '2026/2027', 'semester' => 'Ganjil'],
        ['is_active' => false]
    );
});

test('admin can access master data kelas page and see tabs and selected class details', function () {
    $class = Classes::firstOrCreate(['name' => 'X MIPA 1'], ['grade_level' => 'X']);

    $response = $this->actingAs($this->admin)->get(route('admin.classes.index'));

    $response->assertStatus(200);
    $response->assertViewIs('admin.master-data.data-kelas');
    $response->assertViewHas('classes');
    $response->assertViewHas('selectedClass');
    $response->assertViewHas('activeYear');
    $response->assertSee('Data Kelas');
    $response->assertSee($class->name);
});

test('admin can filter / switch active tab by class_id query param', function () {
    $classX = Classes::firstOrCreate(['name' => 'X MIPA 1'], ['grade_level' => 'X']);
    $classXI = Classes::firstOrCreate(['name' => 'XI MIPA 1'], ['grade_level' => 'XI']);

    $response = $this->actingAs($this->admin)->get(route('admin.classes.index', ['class_id' => $classXI->id]));

    $response->assertStatus(200);
    $response->assertViewHas('selectedClass', function ($selected) use ($classXI) {
        return $selected->id === $classXI->id;
    });
    $response->assertSee($classXI->name);
});

test('admin can create a new class', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.classes.store'), [
        'name' => 'X BAHASA 1',
        'grade_level' => 'X',
        'teacher_id' => null,
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('classes', [
        'name' => 'X BAHASA 1',
        'grade_level' => 'X',
    ]);
});

test('admin can update homeroom teacher for a class', function () {
    $teacherUser = User::firstOrCreate(
        ['email' => 'guru_wali@test.com'],
        ['name' => 'Guru Wali', 'password' => bcrypt('password')]
    );
    $teacher = Teacher::firstOrCreate(
        ['user_id' => $teacherUser->id],
        ['nip' => '19876543210', 'phone' => '08123456789']
    );

    $class = Classes::firstOrCreate(['name' => 'X MIPA 1'], ['grade_level' => 'X']);

    $response = $this->actingAs($this->admin)->put(route('admin.classes.teacher.update', $class->id), [
        'teacher_id' => $teacher->id,
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('classes', [
        'id' => $class->id,
        'teacher_id' => $teacher->id,
    ]);
});

test('admin can add and remove student from a class', function () {
    $class = Classes::firstOrCreate(['name' => 'X MIPA 1'], ['grade_level' => 'X']);

    $student = Student::firstOrCreate(
        ['nisn' => '9999990001'],
        [
            'name' => 'Siswa Uji Coba',
            'gender' => 'L',
            'class_id' => null,
            'status' => 'aktif',
        ]
    );

    // Tambahkan siswa ke kelas
    $response = $this->actingAs($this->admin)->post(route('admin.classes.students.add', $class->id), [
        'student_ids' => [$student->id],
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'class_id' => $class->id,
    ]);
    $this->assertDatabaseHas('student_class_histories', [
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $this->activeYear->id,
        'status' => 'aktif',
    ]);

    // Keluarkan siswa dari kelas
    $removeResponse = $this->actingAs($this->admin)->delete(route('admin.classes.students.remove', [$class->id, $student->id]));

    $removeResponse->assertSessionHas('success');
    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'class_id' => null,
    ]);
});

test('admin can access promotion preview page via dedicated route and see sidebar menu', function () {
    $class = Classes::firstOrCreate(['name' => 'X MIPA 1'], ['grade_level' => 'X']);
    $student = Student::firstOrCreate(
        ['nisn' => '9999990005'],
        ['name' => 'Siswa Test Preview', 'gender' => 'L', 'class_id' => $class->id, 'status' => 'aktif']
    );

    // Akses via route baru admin.promotion.index
    $response = $this->actingAs($this->admin)->get(route('admin.promotion.index', [
        'source_class_id' => 'all',
        'target_academic_year_id' => $this->targetYear->id,
    ]));

    $response->assertStatus(200);
    $response->assertViewIs('admin.master-data.kenaikan-kelas');
    $response->assertSee('Kenaikan Kelas');
    $response->assertSee($class->name);
    $response->assertSee('Akademik');
    $response->assertSee($student->name);

    // Akses via route alias lama tetap berfungsi
    $responseAlias = $this->actingAs($this->admin)->get(route('admin.classes.promotion.preview'));
    $responseAlias->assertStatus(200);
});

test('mass promotion safely promotes classes, graduates class XII, and archives past history', function () {
    $classX = Classes::firstOrCreate(['name' => 'X MIPA 1'], ['grade_level' => 'X']);
    $classXI = Classes::firstOrCreate(['name' => 'XI MIPA 1'], ['grade_level' => 'XI']);
    $classXII = Classes::firstOrCreate(['name' => 'XII MIPA 1'], ['grade_level' => 'XII']);

    // Siswa Kelas X (akan naik ke XI)
    $studentX = Student::firstOrCreate(
        ['nisn' => '9999990010'],
        ['name' => 'Siswa Kelas X', 'gender' => 'L', 'class_id' => $classX->id, 'status' => 'aktif']
    );
    StudentClassHistory::updateOrCreate(
        ['student_id' => $studentX->id, 'academic_year_id' => $this->activeYear->id],
        ['class_id' => $classX->id, 'status' => 'aktif']
    );

    // Siswa Kelas XI (akan naik ke XII)
    $studentXI = Student::firstOrCreate(
        ['nisn' => '9999990011'],
        ['name' => 'Siswa Kelas XI', 'gender' => 'P', 'class_id' => $classXI->id, 'status' => 'aktif']
    );
    StudentClassHistory::updateOrCreate(
        ['student_id' => $studentXI->id, 'academic_year_id' => $this->activeYear->id],
        ['class_id' => $classXI->id, 'status' => 'aktif']
    );

    // Siswa Kelas XII (akan lulus)
    $studentXII = Student::firstOrCreate(
        ['nisn' => '9999990012'],
        ['name' => 'Siswa Kelas XII', 'gender' => 'L', 'class_id' => $classXII->id, 'status' => 'aktif']
    );
    StudentClassHistory::updateOrCreate(
        ['student_id' => $studentXII->id, 'academic_year_id' => $this->activeYear->id],
        ['class_id' => $classXII->id, 'status' => 'aktif']
    );

    // Jalankan Kenaikan Kelas Massal
    $response = $this->actingAs($this->admin)->post(route('admin.classes.promotion.process'), [
        'target_academic_year_id' => $this->targetYear->id,
        'set_target_as_active' => true,
        'promotions' => [
            0 => [
                'selected' => '1',
                'student_id' => $studentX->id,
                'action' => 'naik',
                'target_class_id' => $classXI->id,
            ],
            1 => [
                'selected' => '1',
                'student_id' => $studentXI->id,
                'action' => 'naik',
                'target_class_id' => $classXII->id,
            ],
            2 => [
                'selected' => '1',
                'student_id' => $studentXII->id,
                'action' => 'lulus',
                'target_class_id' => null,
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.classes.index'));
    $response->assertSessionHas('success');

    // 1. Verifikasi Siswa Kelas X -> Naik ke Kelas XI
    $studentX->refresh();
    expect($studentX->class_id)->toBe($classXI->id)
        ->and($studentX->status)->toBe('aktif');

    // Histori lama tahun 2025/2026 siswa X tetap mencatat kelas X dengan status naik_kelas
    $this->assertDatabaseHas('student_class_histories', [
        'student_id' => $studentX->id,
        'academic_year_id' => $this->activeYear->id,
        'class_id' => $classX->id,
        'status' => 'naik_kelas',
    ]);
    // Histori baru tahun 2026/2027 mencatat kelas XI dengan status aktif
    $this->assertDatabaseHas('student_class_histories', [
        'student_id' => $studentX->id,
        'academic_year_id' => $this->targetYear->id,
        'class_id' => $classXI->id,
        'status' => 'aktif',
    ]);

    // 2. Verifikasi Siswa Kelas XI -> Naik ke Kelas XII
    $studentXI->refresh();
    expect($studentXI->class_id)->toBe($classXII->id)
        ->and($studentXI->status)->toBe('aktif');

    $this->assertDatabaseHas('student_class_histories', [
        'student_id' => $studentXI->id,
        'academic_year_id' => $this->activeYear->id,
        'class_id' => $classXI->id,
        'status' => 'naik_kelas',
    ]);
    $this->assertDatabaseHas('student_class_histories', [
        'student_id' => $studentXI->id,
        'academic_year_id' => $this->targetYear->id,
        'class_id' => $classXII->id,
        'status' => 'aktif',
    ]);

    // 3. Verifikasi Siswa Kelas XII -> Lulus (Tidak dipindahkan ke jenjang berikutnya)
    $studentXII->refresh();
    expect($studentXII->status)->toBe('lulus')
        ->and($studentXII->class_id)->toBeNull();

    // Histori lama tahun 2025/2026 siswa XII tetap mencatat kelas XII dengan status lulus
    $this->assertDatabaseHas('student_class_histories', [
        'student_id' => $studentXII->id,
        'academic_year_id' => $this->activeYear->id,
        'class_id' => $classXII->id,
        'status' => 'lulus',
    ]);

    // 4. Verifikasi tahun ajaran target telah aktif
    $this->targetYear->refresh();
    expect($this->targetYear->is_active)->toBeTrue();
});

test('teacher can only access and view their own class and cannot access other classes', function () {
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

    $teacherUser = User::firstOrCreate(
        ['email' => 'wali_guru@test.com'],
        ['name' => 'Wali Kelas 1', 'password' => bcrypt('password')]
    );
    if (! $teacherUser->hasRole('teacher')) {
        $teacherUser->assignRole('teacher');
    }

    $teacher = Teacher::firstOrCreate(
        ['user_id' => $teacherUser->id],
        ['nip' => '199001012020011001', 'phone' => '081299990001']
    );

    $myClass = Classes::firstOrCreate(['name' => 'X MIPA 1'], ['grade_level' => 'X', 'teacher_id' => $teacher->id]);
    $myClass->update(['teacher_id' => $teacher->id]);

    $otherClass = Classes::firstOrCreate(['name' => 'X MIPA 2'], ['grade_level' => 'X']);

    $myStudent = Student::firstOrCreate(
        ['nisn' => '9999990088'],
        ['name' => 'Siswa Kelas Saya', 'gender' => 'L', 'class_id' => $myClass->id, 'status' => 'aktif']
    );

    $otherStudent = Student::firstOrCreate(
        ['nisn' => '9999990089'],
        ['name' => 'Siswa Kelas Lain', 'gender' => 'P', 'class_id' => $otherClass->id, 'status' => 'aktif']
    );

    // 1. Data Kelas: Guru hanya melihat kelasnya sendiri
    $responseClasses = $this->actingAs($teacherUser)->get(route('admin.classes.index', ['class_id' => $otherClass->id]));
    $responseClasses->assertStatus(200);
    $responseClasses->assertSee($myClass->name);
    $responseClasses->assertDontSee($otherClass->name);
    $responseClasses->assertSee($myStudent->name);
    $responseClasses->assertDontSee($otherStudent->name);

    // 2. Kenaikan Kelas: Guru hanya melihat kelasnya sendiri
    $responsePromotion = $this->actingAs($teacherUser)->get(route('admin.promotion.index', ['source_class_id' => $otherClass->id]));
    $responsePromotion->assertStatus(200);
    $responsePromotion->assertSee($myClass->name);
    $responsePromotion->assertDontSee($otherClass->name);
    $responsePromotion->assertSee($myStudent->name);
    $responsePromotion->assertDontSee($otherStudent->name);

    // 3. Kenaikan Kelas: Guru dilarang memproses kenaikan kelas milik orang lain
    $responseProcess = $this->actingAs($teacherUser)->post(route('admin.promotion.process'), [
        'source_class_id' => $otherClass->id,
        'target_academic_year_id' => $this->targetYear->id,
        'promotions' => [
            0 => [
                'selected' => '1',
                'student_id' => $otherStudent->id,
                'action' => 'naik',
                'target_class_id' => $otherClass->id,
            ],
        ],
    ]);
    $responseProcess->assertStatus(403);

    // 4. Data Siswa: Guru hanya melihat siswa di kelasnya sendiri
    $responseStudents = $this->actingAs($teacherUser)->get(route('admin.student.index'));
    $responseStudents->assertStatus(200);
    $responseStudents->assertSee($myStudent->name);
    $responseStudents->assertDontSee($otherStudent->name);
});
