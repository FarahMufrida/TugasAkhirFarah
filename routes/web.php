<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UlasanController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\RekomendasiController;
use App\Http\Controllers\UserController;



/*
|--------------------------------------------------------------------------
| ROUTE AWAL
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| ROUTE SETELAH LOGIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', function () {
        return view('profile.index');
    })->name('profile');

    /*
    |--------------------------------------------------------------------------
    | KELOLA PENGGUNA
    |--------------------------------------------------------------------------
    */

    Route::get('/kelola-pengguna',
        [UserController::class, 'index']
    )->name('kelola-pengguna.index');

    // ✅ TAMBAHAN BARU — route untuk simpan pengguna baru
    Route::post('/kelola-pengguna',
        [UserController::class, 'store']
    )->name('kelola-pengguna.store');

    Route::get('/kelola-pengguna/{id}/edit',
        [UserController::class, 'edit']
    )->name('kelola-pengguna.edit');

    Route::put('/kelola-pengguna/{id}',
        [UserController::class, 'update']
    )->name('kelola-pengguna.update');

    Route::get('/kelola-pengguna/{id}/reset-password',
        [UserController::class, 'resetPassword']
    )->name('kelola-pengguna.reset-password');

    Route::put('/kelola-pengguna/{id}/reset-password',
        [UserController::class, 'updatePassword']
    )->name('kelola-pengguna.update-password');

    Route::delete('/kelola-pengguna/{id}',
        [UserController::class, 'destroy']
    )->name('kelola-pengguna.destroy');

    /*
    |--------------------------------------------------------------------------
    | DATA ULASAN
    |--------------------------------------------------------------------------
    */

    Route::get('/ulasan', [UlasanController::class, 'index'])
        ->name('ulasan.index');

    Route::get('/riwayat', [UlasanController::class, 'riwayat'])
        ->name('riwayat.index');

    Route::post('/riwayat/import',
        [UlasanController::class, 'importRiwayat']
    )->name('riwayat.import');

    Route::post('/ulasan/ambil',
        [UlasanController::class, 'ambilData']
    )->name('ulasan.ambil');

    Route::delete('/ulasan/periode/kosongkan',
        [UlasanController::class, 'kosongkanPeriode']
    )->name('ulasan.kosongkan-periode');

    /*
    |--------------------------------------------------------------------------
    | PROSES ANALISIS
    |--------------------------------------------------------------------------
    */

    Route::post('/proses-analisis',
        [UlasanController::class, 'analisisData']
    )->name('proses.analisis');

    /*
    |--------------------------------------------------------------------------
    | HASIL ANALISIS
    |--------------------------------------------------------------------------
    */

    Route::get('/analisis',
        [AnalisisController::class, 'index']
    )->name('analisis.index');

    /*
    |--------------------------------------------------------------------------
    | REKOMENDASI
    |--------------------------------------------------------------------------
    */

    Route::get('/rekomendasi', function () {
        return redirect('/dashboard?tab=rekomendasi');
    })->name('rekomendasi.index');

});

require __DIR__.'/auth.php';