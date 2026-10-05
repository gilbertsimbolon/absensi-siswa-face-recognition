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
    Route::put('/data-kelas/{class}', [ClassesController::class, 'update'])->name('admin.classes.update');
    Route::delete('/data-kelas/{class}', [ClassesController::class, 'destroy'])->name('admin.classes.destroy');

    // Route Data Siswa & Orang Tua
    Route::get('/data-siswa', [StudentController::class, 'index'])->name('admin.student.index');
    Route::post('/data-siswa', [StudentController::class, 'store'])->name('admin.student.store');
    Route::put('/data-siswa/{student}', [StudentController::class, 'update'])->name('admin.student.update');
    Route::delete('/data-siswa/{student}', [StudentController::class, 'destroy'])->name('admin.student.destroy');
});
