<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityDashboardController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/servidor', [ServerController::class, 'index']);

// Panel de seguridad: requiere estar logueado Y ser admin (Gate viewSecurityDashboard)
Route::middleware(['auth', 'can:viewSecurityDashboard'])->group(function () {
    Route::get('/security-dashboard', [SecurityDashboardController::class, 'index'])
        ->name('security.dashboard');

    Route::get('/security-dashboard/export', [SecurityDashboardController::class, 'export'])
        ->name('security.export');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
