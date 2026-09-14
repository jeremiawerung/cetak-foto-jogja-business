<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/internal/login');
        $middleware->redirectUsersTo('/internal');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $interval = max(1, (int) config('services.verifikasi_siswa.sync_interval_minutes', 15));

        $schedule->command('verifikasi-siswa:sync')->cron("*/{$interval} * * * *");
    })
    ->create();
