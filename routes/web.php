<?php

// use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ClassesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'index'])->name('login.index');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class,'logout'])->name('logout');

// Route Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Route Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');

    // Route Data Guru
    Route::get('/data-guru', [TeacherController::class, 'index'])->name('admin.teacher.index');
    Route::post('/data-guru', [TeacherController::class, 'store'])->name('admin.teacher.store');

    // Route Data Kelas
    Route::get('/data-kelas', [ClassesController::class, 'index'])->name('admin.classes.index');
});
