<?php

use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\RenterVerificationController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::view('/dashboard', 'admin.dashboard')->name('dashboard');

        Route::get('/garages/create', [GarageController::class, 'create'])->name('garages.create');
        Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');

        Route::get('/renters', [RenterVerificationController::class, 'index'])->name('renters.index');
        Route::patch('/renters/{user}/approve', [RenterVerificationController::class, 'approve'])->name('renters.approve');
        Route::patch('/renters/{user}/reject', [RenterVerificationController::class, 'reject'])->name('renters.reject');
    });

    Route::middleware('role:renter')->prefix('renter')->name('renter.')->group(function () {
        Route::view('/dashboard', 'renter.dashboard')->name('dashboard');
    });
});