<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PerizinanController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['check.auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/students/search', [StudentController::class, 'liveSearch'])->name('students.search');

    Route::get('/perizinan-kedatangan', function () {
        return view('perizinan_kedatangan');
    })->name('perizinan.kedatangan');

    Route::post('/perizinan-kedatangan/submit', [PerizinanController::class, 'submitKedatangan'])->name('perizinan.kedatangan.submit');

    Route::get('/perizinan-umum', function () {
        return view('perizinan_umum');
    })->name('perizinan.umum');

    Route::post('/perizinan-umum/submit', [PerizinanController::class, 'submitUmum'])->name('perizinan.umum.submit');

    Route::get('/perizinan-khusus', function () {
        return view('perizinan_khusus');
    })->name('perizinan.khusus');

    Route::post('/perizinan-khusus/submit', [PerizinanController::class, 'submitKhusus'])->name('perizinan.khusus.submit');

    Route::get('/account/ganti-password', [AuthController::class, 'showGantiPassword'])->name('account.ganti-password');
    Route::post('/account/ganti-password', [AuthController::class, 'gantiPassword'])->name('account.ganti-password.post');

    Route::get('/laporan', [PerizinanController::class, 'showLaporan'])->name('laporan');
});
