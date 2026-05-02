<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UlasanController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\RekomendasiController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/', function () {
    return view('welcome');
});

// Dashboard SENTARA (harus login)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', function () {
        return view('profile.index');
    })->name('profile');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/ulasan', [UlasanController::class, 'index'])->name('ulasan.index');
    Route::post('/ulasan/ambil', [UlasanController::class, 'ambilData'])->name('ulasan.ambil');
    Route::post('/ulasan/analisis', [UlasanController::class, 'analisisData'])->name('ulasan.analisis');
    Route::post('/ulasan/upload', [UlasanController::class, 'upload'])->name('ulasan.upload');
        Route::get('/hasil-analisis', [AnalisisController::class, 'index'])
    ->name('analisis.index');
   Route::get('/rekomendasi', [RekomendasiController::class, 'index'])
    ->name('rekomendasi.index');;
    Route::get('/', function () {
    return redirect()->route('login');
    
});

});

require __DIR__.'/auth.php';
