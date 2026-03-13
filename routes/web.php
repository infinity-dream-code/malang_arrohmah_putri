<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PerizinanController;
use App\Http\Controllers\PresensiSholatController;
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

    Route::get('/dashboard-presensi-sholat', function () {
        return view('dashboard_presensi_sholat');
    })->name('dashboard.presensi-sholat');

    Route::get('/presensi-sholat/qr', [PresensiSholatController::class, 'showQr'])->name('presensi-sholat.qr');
    Route::post('/presensi-sholat/post-sholat', [PresensiSholatController::class, 'postSholat'])->name('presensi-sholat.post-sholat');

    Route::get('/presensi-haid/qr', [PresensiSholatController::class, 'showHaidQr'])->name('presensi-haid.qr');
    Route::post('/presensi-haid/post-haid', [PresensiSholatController::class, 'postHaid'])->name('presensi-haid.post-haid');

    Route::get('/log-marifah', [PresensiSholatController::class, 'showLogMarifah'])->name('presensi.log-marifah');
    Route::get('/log-presensi', [PresensiSholatController::class, 'showLogPresensi'])->name('presensi.log-presensi');
    Route::get('/log-presensi/export-excel', [PresensiSholatController::class, 'exportLogPresensiExcel'])->name('presensi.log-presensi.export-excel');
    Route::get('/log-presensi/export-pdf', [PresensiSholatController::class, 'exportLogPresensiPdf'])->name('presensi.log-presensi.export-pdf');

    Route::get('/kelola-presensi', [PresensiSholatController::class, 'showKelolaPresensi'])->name('presensi.kelola');
    Route::get('/kelola-presensi/data', [PresensiSholatController::class, 'kelolaPresensiData'])->name('presensi.kelola.data');
    Route::post('/kelola-presensi/update', [PresensiSholatController::class, 'updatePresensi'])->name('presensi.kelola.update');

    Route::get('/rekap-sholat', [PresensiSholatController::class, 'showRekapSholat'])->name('presensi.rekap-sholat');
    Route::get('/rekap-sholat/data', [PresensiSholatController::class, 'rekapSholatData'])->name('presensi.rekap-sholat.data');
    Route::get('/rekap-sholat/export-excel', [PresensiSholatController::class, 'exportRekapSholatExcel'])->name('presensi.rekap-sholat.export-excel');
    Route::get('/rekap-sholat/export-pdf', [PresensiSholatController::class, 'exportRekapSholatPdf'])->name('presensi.rekap-sholat.export-pdf');

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

    Route::get('/presensi/account/ganti-password', [AuthController::class, 'showGantiPasswordPresensi'])->name('presensi.account.ganti-password');
    Route::post('/presensi/account/ganti-password', [AuthController::class, 'gantiPassword'])->name('presensi.account.ganti-password.post');

    Route::get('/laporan', [PerizinanController::class, 'showLaporan'])->name('laporan');
});
