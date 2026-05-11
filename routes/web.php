<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UlasanController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\RekomendasiController;

/*
|--------------------------------------------------------------------------
| ROUTE AWAL
|--------------------------------------------------------------------------
*/

// hanya SATU route '/'
Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| ROUTE SETELAH LOGIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // ✅ DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ✅ PROFILE (ini yang kamu tanya)
    Route::get('/profile', function () {
        return view('profile.index');
    })->name('profile');

    // kalau mau aktifkan update:
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ✅ DATA ULASAN
    Route::get('/ulasan', [UlasanController::class, 'index'])
        ->name('ulasan.index');

    Route::get('/riwayat', [UlasanController::class, 'riwayat'])
        ->name('riwayat.index');

    Route::post('/riwayat/import', [UlasanController::class, 'importRiwayat'])
        ->name('riwayat.import');

    Route::post('/ulasan/ambil', [UlasanController::class, 'ambilData'])
        ->name('ulasan.ambil');

    Route::delete('/ulasan/periode/kosongkan', [UlasanController::class, 'kosongkanPeriode'])
        ->name('ulasan.kosongkan-periode');

    // ✅ PROSES ANALISIS
    Route::post('/proses-analisis', [UlasanController::class, 'analisisData'])
        ->name('proses.analisis');

    // ✅ HASIL ANALISIS
    Route::get('/analisis', [AnalisisController::class, 'index'])
        ->name('analisis.index');

    // ✅ REKOMENDASI
    Route::get('/rekomendasi', function() {
    return redirect('/dashboard?tab=rekomendasi');
})->name('rekomendasi.index');

});

require __DIR__.'/auth.php';
