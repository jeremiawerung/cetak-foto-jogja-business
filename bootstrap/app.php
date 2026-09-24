<?php

use App\Http\Middleware\EnsureCanAccessArea;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['area' => EnsureCanAccessArea::class]);

        // /admin (Kelola Katalog) dan /internal (Verifikasi Siswa) punya login
        // terpisah - tamu/pengguna yang login diarahkan ke halaman yang sesuai
        // dengan area yang sedang diakses, bukan selalu ke /internal.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin*') ? route('admin.login') : route('internal.login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('admin*') ? route('admin.dashboard') : route('internal.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $interval = max(1, (int) config('services.verifikasi_siswa.sync_interval_minutes', 15));

        // ->call() jalan di proses PHP yang sama, bukan spawn proses baru seperti
        // ->command() - hosting production tidak punya proc_open jadi spawn proses
        // selalu gagal (lihat SignatureMimeTypeGuesser untuk kendala serupa).
        $schedule->call(fn () => Artisan::call('verifikasi-siswa:sync'))->cron("*/{$interval} * * * *");
    })
    ->create();
