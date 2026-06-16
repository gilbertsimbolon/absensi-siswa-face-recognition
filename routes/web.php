<?php

// use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'index'])->name('login.index');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->name('dashboard');
