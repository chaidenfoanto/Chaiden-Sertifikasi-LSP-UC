<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\RenterVerificationController;
use App\Http\Controllers\Admin\RentalExtensionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Renter\RentalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/features', [PageController::class, 'features'])->name('features');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/garages/create', [GarageController::class, 'create'])->name('garages.create');
        Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');

        Route::get('/renters', [RenterVerificationController::class, 'index'])->name('renters.index');
        Route::patch('/renters/{user}/approve', [RenterVerificationController::class, 'approve'])->name('renters.approve');
        Route::patch('/renters/{user}/reject', [RenterVerificationController::class, 'reject'])->name('renters.reject');

        Route::get('/rentals', [RentalExtensionController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/{rental}/extension-quote', [RentalExtensionController::class, 'quote'])->name('rentals.quote');
        Route::post('/rentals/{rental}/extend', [RentalExtensionController::class, 'store'])->name('rentals.extend');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    Route::middleware('role:renter')->prefix('renter')->name('renter.')->group(function () {
        Route::view('/dashboard', 'renter.dashboard')->name('dashboard');

        Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/create', [RentalController::class, 'create'])->name('rentals.create');
        Route::get('/rentals/quote', [RentalController::class, 'quote'])->name('rentals.quote');
        Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
    });
});