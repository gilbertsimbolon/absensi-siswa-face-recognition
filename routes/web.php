<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClassesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Otentikasi Masuk & Keluar
Route::get('/', [AuthController::class, 'index'])->name('login.index');
Route::get('/masuk', [AuthController::class, 'index'])->name('masuk.index');
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/masuk', [AuthController::class, 'login'])->name('login.store');
Route::post('/login', [AuthController::class, 'login']);

Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
Route::post('/logout', [AuthController::class, 'logout']);

// Route Admin & Guru (Role-based access)
Route::middleware(['auth', 'role:admin|teacher'])->prefix('admin')->group(function () {
    // Route Beranda (Bahasa Indonesia)
    Route::get('/beranda', [DashboardController::class, 'index'])->name('admin.dashboard.index');
    Route::get('/dashboard', fn () => redirect()->route('admin.dashboard.index'));

    // Route Khusus Admin (Kelola Guru & Struktur Kelas Global)
    Route::middleware('role:admin')->group(function () {
        // Route Data Guru
        Route::get('/data-guru', [TeacherController::class, 'index'])->name('admin.teacher.index');
        Route::post('/data-guru', [TeacherController::class, 'store'])->name('admin.teacher.store');
        Route::put('/data-guru/{teacher}', [TeacherController::class, 'update'])->name('admin.teacher.update');
        Route::delete('/data-guru/{teacher}', [TeacherController::class, 'destroy'])->name('admin.teacher.destroy');

        // Struktur Kelas & Tahun Ajaran
        Route::post('/data-kelas', [ClassesController::class, 'store'])->name('admin.classes.store');
        Route::put('/data-kelas/{class}', [ClassesController::class, 'update'])->name('admin.classes.update');
        Route::delete('/data-kelas/{class}', [ClassesController::class, 'destroy'])->name('admin.classes.destroy');
        Route::put('/data-kelas/{class}/wali-kelas', [ClassesController::class, 'updateTeacher'])->name('admin.classes.teacher.update');
        Route::post('/data-kelas/tahun-ajaran', [ClassesController::class, 'storeAcademicYear'])->name('admin.classes.academic-year.store');
        Route::put('/data-kelas/tahun-ajaran/{academicYear}/aktifkan', [ClassesController::class, 'setActiveAcademicYear'])->name('admin.classes.academic-year.set-active');
    });

    // Route Data Kelas (Bisa diakses Admin & Guru Wali Kelas)
    Route::get('/data-kelas', [ClassesController::class, 'index'])->name('admin.classes.index');
    Route::post('/data-kelas/{class}/tambah-siswa', [ClassesController::class, 'addStudents'])->name('admin.classes.students.add');
    Route::delete('/data-kelas/{class}/hapus-siswa/{student}', [ClassesController::class, 'removeStudent'])->name('admin.classes.students.remove');

    // Route Kenaikan Kelas (Menu Akademik - Admin & Guru Wali Kelas)
    Route::get('/kenaikan-kelas', [ClassesController::class, 'promotionPreview'])->name('admin.promotion.index');
    Route::post('/kenaikan-kelas', [ClassesController::class, 'promoteProcess'])->name('admin.promotion.process');
    Route::get('/data-kelas/kenaikan-kelas', [ClassesController::class, 'promotionPreview'])->name('admin.classes.promotion.preview');
    Route::post('/data-kelas/kenaikan-kelas', [ClassesController::class, 'promoteProcess'])->name('admin.classes.promotion.process');

    // Route Data Siswa & Orang Tua (Admin & Guru Wali Kelas)
    Route::get('/data-siswa', [StudentController::class, 'index'])->name('admin.student.index');
    Route::post('/data-siswa', [StudentController::class, 'store'])->name('admin.student.store');
    Route::put('/data-siswa/{student}', [StudentController::class, 'update'])->name('admin.student.update');
    Route::delete('/data-siswa/{student}', [StudentController::class, 'destroy'])->name('admin.student.destroy');

    // Route Absensi & Rekapitulasi (Admin & Guru Wali Kelas)
    Route::get('/absensi', [AttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::post('/absensi', [AttendanceController::class, 'storeOrUpdate'])->name('admin.attendance.store');
    Route::post('/absensi/massal', [AttendanceController::class, 'bulkMark'])->name('admin.attendance.bulk');
    Route::post('/absensi/bulk', [AttendanceController::class, 'bulkMark']);
    Route::delete('/absensi/{attendance}', [AttendanceController::class, 'destroy'])->name('admin.attendance.destroy');

    // Route Pindai Wajah Otomatis (Scanner HP / Desktop)
    Route::get('/absensi/pindai', [AttendanceController::class, 'pindai'])->name('admin.attendance.pindai');
    Route::post('/absensi/pindai/kamera', [AttendanceController::class, 'gantiKamera'])->name('admin.attendance.pindai.kamera');

    Route::get('/rekapitulasi', [AttendanceController::class, 'recap'])->name('admin.attendance.recap');
    Route::get('/rekapitulasi/cetak', [AttendanceController::class, 'printRecap'])->name('admin.attendance.print');
});

// Endpoint Proses Presensi Pindai Wajah (Akses Web Scanner & Kiosk / AI)
Route::post('/admin/absensi/proses-pindai', [AttendanceController::class, 'prosesPindai'])->name('admin.attendance.proses-pindai');
Route::post('/api/presensi/pindai-wajah', [AttendanceController::class, 'prosesPindai'])->name('api.presensi.pindai-wajah');
Route::get('/api/presensi/daftar-siswa', [AttendanceController::class, 'daftarSiswa'])->name('api.presensi.daftar-siswa');

// Shortcut URL Pindai untuk Akses Cepat di HP / Kiosk (Dapat diakses langsung)
Route::get('/pindai', [AttendanceController::class, 'pindai'])->name('attendance.pindai');
Route::post('/pindai/kamera', [AttendanceController::class, 'gantiKamera'])->name('attendance.pindai.kamera');
