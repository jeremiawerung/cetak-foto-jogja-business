<?php

use App\Http\Controllers\CetakFotoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Internal\AuthController as InternalAuthController;
use App\Http\Controllers\Internal\DashboardController as InternalDashboardController;
use App\Http\Controllers\Internal\KartuPelajarBatchController as InternalKartuPelajarBatchController;
use App\Http\Controllers\Internal\KartuPelajarController as InternalKartuPelajarController;
use App\Http\Controllers\Internal\VerifikasiSiswaFieldController;
use App\Http\Controllers\Internal\VerifikasiSiswaProyekController;
use App\Http\Controllers\Internal\VerifikasiSiswaResolusiController;
use App\Http\Controllers\Internal\VerifikasiSiswaRosterController;
use App\Http\Controllers\SewaFotograferController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('cetak-foto')->name('cetak-foto.')->group(function () {
    Route::get('/', [CetakFotoController::class, 'index'])->name('index');
    Route::post('/order', [CetakFotoController::class, 'store'])->name('order');
});

Route::prefix('sewa-fotografer')->name('sewa-fotografer.')->group(function () {
    Route::get('/', [SewaFotograferController::class, 'index'])->name('index');
    Route::get('/ketersediaan', [SewaFotograferController::class, 'ketersediaan'])->name('ketersediaan');
    Route::post('/booking', [SewaFotograferController::class, 'store'])->name('booking');
});

Route::prefix('internal')->name('internal.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [InternalAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [InternalAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/', [InternalDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [InternalAuthController::class, 'logout'])->name('logout');

        // Nonaktif sementara: fitur QA Kartu Pelajar (audit+matching 2 file mentah) masih
        // belum lengkap dan sudah digantikan pendekatan lain (lihat verifikasi-siswa di
        // bawah). Kode/tabelnya dibiarkan dorman, tidak dihapus. Aktifkan lagi dengan
        // menghapus komentar blok ini kalau ternyata masih dibutuhkan.
        // Route::prefix('kartu-pelajar')->name('kartu-pelajar.')->group(function () {
        //     Route::get('/', [InternalKartuPelajarController::class, 'index'])->name('index');
        //     Route::post('/audit', [InternalKartuPelajarController::class, 'audit'])->name('audit');
        //     Route::get('/audit/unduh/{sumber}', [InternalKartuPelajarController::class, 'unduhCsv'])
        //         ->whereIn('sumber', ['excel', 'gform'])
        //         ->name('audit.unduh');
        //     Route::post('/verifikasi-ai', [InternalKartuPelajarController::class, 'verifikasiAi'])->name('verifikasi-ai');
        //
        //     Route::prefix('batch')->name('batch.')->group(function () {
        //         Route::get('/', [InternalKartuPelajarBatchController::class, 'index'])->name('index');
        //         Route::get('/{batch}', [InternalKartuPelajarBatchController::class, 'show'])->name('show');
        //         Route::post('/{batch}/rows/{row}/konfirmasi', [InternalKartuPelajarBatchController::class, 'confirm'])->name('rows.confirm');
        //         Route::post('/{batch}/rows/{row}/tolak', [InternalKartuPelajarBatchController::class, 'reject'])->name('rows.reject');
        //         Route::post('/{batch}/rows/{row}/hubungkan', [InternalKartuPelajarBatchController::class, 'link'])->name('rows.link');
        //         Route::post('/{batch}/rows/{row}/tanpa-pasangan', [InternalKartuPelajarBatchController::class, 'noPair'])->name('rows.no-pair');
        //         Route::post('/{batch}/rows/proses-massal', [InternalKartuPelajarBatchController::class, 'bulkResolve'])->name('rows.bulk-resolve');
        //         Route::get('/{batch}/ekspor', [InternalKartuPelajarBatchController::class, 'exportPreview'])->name('export.preview');
        //         Route::get('/{batch}/ekspor/unduh', [InternalKartuPelajarBatchController::class, 'exportDownload'])->name('export.download');
        //         Route::get('/{batch}/visual-qa', [InternalKartuPelajarBatchController::class, 'visualQa'])->name('visual-qa');
        //         Route::post('/{batch}/visual-qa/upload', [InternalKartuPelajarBatchController::class, 'visualQaUpload'])->name('visual-qa.upload');
        //     });
        // });

        Route::prefix('verifikasi-siswa')->name('verifikasi-siswa.')->group(function () {
            Route::get('/', [VerifikasiSiswaProyekController::class, 'index'])->name('index');
            Route::post('/', [VerifikasiSiswaProyekController::class, 'store'])->name('store');
            Route::get('/{proyek}', [VerifikasiSiswaProyekController::class, 'show'])->name('show');
            Route::post('/{proyek}/sinkron', [VerifikasiSiswaProyekController::class, 'syncNow'])->name('sinkron');
            Route::get('/{proyek}/ekspor/{kelas}', [VerifikasiSiswaProyekController::class, 'exportKelas'])->name('ekspor');

            Route::post('/{proyek}/roster', [VerifikasiSiswaRosterController::class, 'upload'])->name('roster.upload');
            Route::get('/{proyek}/roster/tinjau', [VerifikasiSiswaRosterController::class, 'tinjau'])->name('roster.tinjau');
            Route::post('/{proyek}/roster/simpan', [VerifikasiSiswaRosterController::class, 'simpan'])->name('roster.simpan');

            Route::post('/{proyek}/respons/{response}/konfirmasi', [VerifikasiSiswaResolusiController::class, 'confirm'])->name('respons.confirm');
            Route::post('/{proyek}/respons/{response}/tolak', [VerifikasiSiswaResolusiController::class, 'reject'])->name('respons.reject');
            Route::post('/{proyek}/respons/{response}/hubungkan', [VerifikasiSiswaResolusiController::class, 'link'])->name('respons.link');
            Route::post('/{proyek}/respons/proses-massal', [VerifikasiSiswaResolusiController::class, 'bulkResolve'])->name('respons.bulk-resolve');
            Route::post('/{proyek}/perbedaan/proses-massal', [VerifikasiSiswaResolusiController::class, 'bulkResolveDiscrepancy'])->name('perbedaan.bulk-resolve');
            Route::post('/{proyek}/siswa/{siswa}/kunci', [VerifikasiSiswaResolusiController::class, 'lock'])->name('siswa.lock');
            Route::post('/{proyek}/siswa/{siswa}/buka-kunci', [VerifikasiSiswaResolusiController::class, 'unlock'])->name('siswa.unlock');

            Route::get('/{proyek}/field', [VerifikasiSiswaFieldController::class, 'index'])->name('field.index');
            Route::post('/{proyek}/field', [VerifikasiSiswaFieldController::class, 'storeField'])->name('field.store');
            Route::post('/{proyek}/field/{field}/hapus', [VerifikasiSiswaFieldController::class, 'destroyField'])->name('field.destroy');
            Route::post('/{proyek}/ekspor-kolom', [VerifikasiSiswaFieldController::class, 'storeExportColumn'])->name('ekspor-kolom.store');
            Route::post('/{proyek}/ekspor-kolom/{column}/hapus', [VerifikasiSiswaFieldController::class, 'destroyExportColumn'])->name('ekspor-kolom.destroy');
        });
    });
});
