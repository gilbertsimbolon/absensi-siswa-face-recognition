<?php

// use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ClassesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'index'])->name('login.index');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Route Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Route Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');

    // Route Data Guru
    Route::get('/data-guru', [TeacherController::class, 'index'])->name('admin.teacher.index');
    Route::post('/data-guru', [TeacherController::class, 'store'])->name('admin.teacher.store');
    Route::put('/data-guru/{teacher}', [TeacherController::class, 'update'])->name('admin.teacher.update');
    Route::delete('/data-guru/{teacher}', [TeacherController::class, 'destroy'])->name('admin.teacher.destroy');

    // Route Data Kelas
    Route::get('/data-kelas', [ClassesController::class, 'index'])->name('admin.classes.index');
    Route::post('/data-kelas', [ClassesController::class, 'store'])->name('admin.classes.store');
    // Route Kenaikan Kelas (Menu Akademik)
    Route::get('/kenaikan-kelas', [ClassesController::class, 'promotionPreview'])->name('admin.promotion.index');
    Route::post('/kenaikan-kelas', [ClassesController::class, 'promoteProcess'])->name('admin.promotion.process');
    Route::get('/data-kelas/kenaikan-kelas', [ClassesController::class, 'promotionPreview'])->name('admin.classes.promotion.preview');
    Route::post('/data-kelas/kenaikan-kelas', [ClassesController::class, 'promoteProcess'])->name('admin.classes.promotion.process');
    Route::post('/data-kelas/tahun-ajaran', [ClassesController::class, 'storeAcademicYear'])->name('admin.classes.academic-year.store');
    Route::put('/data-kelas/tahun-ajaran/{academicYear}/aktifkan', [ClassesController::class, 'setActiveAcademicYear'])->name('admin.classes.academic-year.set-active');
    Route::put('/data-kelas/{class}/wali-kelas', [ClassesController::class, 'updateTeacher'])->name('admin.classes.teacher.update');
    Route::post('/data-kelas/{class}/tambah-siswa', [ClassesController::class, 'addStudents'])->name('admin.classes.students.add');
    Route::delete('/data-kelas/{class}/hapus-siswa/{student}', [ClassesController::class, 'removeStudent'])->name('admin.classes.students.remove');
    Route::put('/data-kelas/{class}', [ClassesController::class, 'update'])->name('admin.classes.update');
    Route::delete('/data-kelas/{class}', [ClassesController::class, 'destroy'])->name('admin.classes.destroy');

    // Route Data Siswa & Orang Tua
    Route::get('/data-siswa', [StudentController::class, 'index'])->name('admin.student.index');
    Route::post('/data-siswa', [StudentController::class, 'store'])->name('admin.student.store');
    Route::put('/data-siswa/{student}', [StudentController::class, 'update'])->name('admin.student.update');
    Route::delete('/data-siswa/{student}', [StudentController::class, 'destroy'])->name('admin.student.destroy');
});
