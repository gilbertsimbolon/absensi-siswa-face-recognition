<?php

// use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ClassesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'index'])->name('login.index');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class,'logout'])->name('logout');

// Route Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');
    Route::get('/data-kelas', [ClassesController::class, 'index'])->name('admin.classes.index');
});
