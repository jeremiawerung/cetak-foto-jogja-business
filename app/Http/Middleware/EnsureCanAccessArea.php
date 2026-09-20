<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatas akses per area admin - "internal" (Verifikasi Siswa) dan "katalog"
 * (Kelola Katalog Cetak Foto) punya login & user terpisah, kecuali role "super"
 * yang bisa akses dua-duanya. Dipasang setelah middleware "auth" di masing-masing
 * route group (lihat routes/web.php).
 */
class EnsureCanAccessArea
{
    public function handle(Request $request, Closure $next, string $area): Response
    {
        $user = $request->user();

        $bolehAkses = match ($area) {
            'internal' => $user?->canAccessInternal(),
            'katalog' => $user?->canAccessKatalog(),
            default => false,
        };

        abort_unless($bolehAkses, 403);

        return $next($request);
    }
}
