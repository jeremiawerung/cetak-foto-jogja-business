<?php

use App\Http\Controllers\CetakFotoController;
use App\Http\Controllers\HomeController;
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
