<?php

namespace App\Providers;

use App\Services\Upload\SignatureMimeTypeGuesser;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\MimeTypes;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lihat SignatureMimeTypeGuesser - hosting produksi tidak selalu punya
        // ext-fileinfo/proc_open yang dibutuhkan guesser bawaan Symfony, jadi validasi
        // `mimes:` di semua form upload bisa fatal error tanpa ini.
        MimeTypes::getDefault()->registerGuesser(new SignatureMimeTypeGuesser);
    }
}
