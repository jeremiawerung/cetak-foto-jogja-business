<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BookingStudioController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\KategoriProdukController;
use App\Http\Controllers\Admin\KategoriProdukItemController;
use App\Http\Controllers\Admin\KategoriProdukTierController;
use App\Http\Controllers\Admin\NotifikasiController;
use App\Http\Controllers\Admin\PengaturanPembayaranController;
use App\Http\Controllers\Admin\PrintOrderController;
use App\Http\Controllers\CekPesananController;
use App\Http\Controllers\CetakFotoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\ProdukDetailController;
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
});

Route::get('/produk/{kategori}/{item?}', [ProdukDetailController::class, 'show'])->name('produk.show');

Route::prefix('keranjang')->name('keranjang.')->group(function () {
    Route::get('/', [KeranjangController::class, 'index'])->name('index');
    Route::post('/tambah', [KeranjangController::class, 'tambah'])->name('tambah');
    Route::post('/hapus/{index}', [KeranjangController::class, 'hapus'])->whereNumber('index')->name('hapus');
});

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/informasi', [CheckoutController::class, 'informasiForm'])->name('informasi');
    Route::post('/informasi', [CheckoutController::class, 'informasiSimpan'])->name('informasi.simpan');

    Route::get('/pengiriman', [CheckoutController::class, 'pengirimanForm'])->name('pengiriman');
    Route::post('/pengiriman', [CheckoutController::class, 'pengirimanSimpan'])->name('pengiriman.simpan');
    Route::get('/pengiriman/cari-tujuan', [CheckoutController::class, 'cariTujuan'])->name('pengiriman.cari-tujuan');
    Route::get('/pengiriman/opsi-ongkir', [CheckoutController::class, 'opsiOngkir'])->name('pengiriman.opsi-ongkir');

    Route::get('/konfirmasi', [CheckoutController::class, 'konfirmasiForm'])->name('konfirmasi');
    Route::post('/konfirmasi', [CheckoutController::class, 'konfirmasiSimpan'])->name('konfirmasi.simpan');

    Route::get('/sukses/{printOrder:nomor_pesanan}', [CheckoutController::class, 'sukses'])->name('sukses');
});

Route::get('/cek-pesanan', [CekPesananController::class, 'index'])->name('cek-pesanan');

Route::prefix('sewa-fotografer')->name('sewa-fotografer.')->group(function () {
    Route::get('/', [SewaFotograferController::class, 'index'])->name('index');
    Route::get('/ketersediaan', [SewaFotograferController::class, 'ketersediaan'])->name('ketersediaan');
    Route::post('/booking', [SewaFotograferController::class, 'store'])->name('booking');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
    });

    Route::middleware(['auth', 'area:katalog'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::prefix('kategori-produk')->name('kategori-produk.')->group(function () {
            Route::get('/', [KategoriProdukController::class, 'index'])->name('index');
            Route::post('/', [KategoriProdukController::class, 'store'])->name('store');
            Route::get('/{kategoriProduk}', [KategoriProdukController::class, 'show'])->name('show');
            Route::post('/{kategoriProduk}', [KategoriProdukController::class, 'update'])->name('update');
            Route::post('/{kategoriProduk}/status', [KategoriProdukController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{kategoriProduk}/hapus', [KategoriProdukController::class, 'destroy'])->name('destroy');

            Route::post('/{kategoriProduk}/item', [KategoriProdukItemController::class, 'store'])->name('item.store');
            Route::post('/{kategoriProduk}/item/{item}', [KategoriProdukItemController::class, 'update'])->name('item.update');
            Route::post('/{kategoriProduk}/item/{item}/status', [KategoriProdukItemController::class, 'toggleActive'])->name('item.toggle-active');
            Route::post('/{kategoriProduk}/item/{item}/hapus', [KategoriProdukItemController::class, 'destroy'])->name('item.destroy');

            Route::post('/{kategoriProduk}/tier', [KategoriProdukTierController::class, 'store'])->name('tier.store');
            Route::post('/{kategoriProduk}/tier/{tier}', [KategoriProdukTierController::class, 'update'])->name('tier.update');
            Route::post('/{kategoriProduk}/tier/{tier}/hapus', [KategoriProdukTierController::class, 'destroy'])->name('tier.destroy');
        });

        Route::prefix('order-cetak-foto')->name('order-cetak-foto.')->group(function () {
            Route::get('/', [PrintOrderController::class, 'index'])->name('index');
            Route::get('/{printOrder}', [PrintOrderController::class, 'show'])->name('show');
            Route::post('/{printOrder}/status', [PrintOrderController::class, 'updateStatus'])->name('status');
            Route::post('/{printOrder}/pembayaran', [PrintOrderController::class, 'updatePembayaran'])->name('pembayaran');
            Route::get('/{printOrder}/bukti-transfer', [PrintOrderController::class, 'buktiTransfer'])->name('bukti-transfer');
            Route::get('/{printOrder}/label', [PrintOrderController::class, 'label'])->name('label');
            Route::get('/{printOrder}/item/{item}/file/{index}', [PrintOrderController::class, 'downloadFile'])->whereNumber('index')->name('download-file');
            Route::get('/{printOrder}/unduh-semua', [PrintOrderController::class, 'downloadAll'])->name('download-all');
        });

        Route::prefix('booking-studio')->name('booking-studio.')->group(function () {
            Route::get('/', [BookingStudioController::class, 'index'])->name('index');
            Route::get('/{photographerBooking}', [BookingStudioController::class, 'show'])->name('show');
            Route::post('/{photographerBooking}/status', [BookingStudioController::class, 'updateStatus'])->name('status');
        });

        Route::prefix('pengaturan-pembayaran')->name('pengaturan-pembayaran.')->group(function () {
            Route::get('/', [PengaturanPembayaranController::class, 'edit'])->name('edit');
            Route::post('/', [PengaturanPembayaranController::class, 'update'])->name('update');
        });

        Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
            Route::get('/', [NotifikasiController::class, 'index'])->name('index');
            Route::post('/{notifikasi}/baca', [NotifikasiController::class, 'tandaiDibaca'])->name('baca');
            Route::post('/baca-semua', [NotifikasiController::class, 'tandaiSemuaDibaca'])->name('baca-semua');
        });
    });
});

Route::prefix('internal')->name('internal.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [InternalAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [InternalAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
    });

    Route::middleware(['auth', 'area:internal'])->group(function () {
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
