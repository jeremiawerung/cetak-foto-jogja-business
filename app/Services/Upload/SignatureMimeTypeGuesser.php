<?php

namespace App\Services\Upload;

use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Tebak MIME type dari "magic bytes" di awal file, murni PHP tanpa bergantung ke
 * ext-fileinfo atau proc_open (dua-duanya tidak bisa diandalkan di hosting produksi -
 * lihat bootstrap/polyfills.php untuk kasus serupa dengan mb_split). Tanpa ini,
 * validasi `mimes:` di semua form upload (bukti transfer, foto cetak, roster
 * verifikasi siswa, dst) fatal error alih-alih gagal validasi dengan wajar.
 */
class SignatureMimeTypeGuesser implements MimeTypeGuesserInterface
{
    public function isGuesserSupported(): bool
    {
        return true;
    }

    public function guessMimeType(string $path): ?string
    {
        if (! is_readable($path)) {
            return null;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $header = fread($handle, 16);
        fclose($handle);

        if ($header === false || $header === '') {
            return null;
        }

        return match (true) {
            str_starts_with($header, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($header, "\x89PNG\r\n\x1a\n") => 'image/png',
            str_starts_with($header, 'GIF87a'), str_starts_with($header, 'GIF89a') => 'image/gif',
            str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP' => 'image/webp',
            str_starts_with($header, '%PDF-') => 'application/pdf',
            str_starts_with($header, '8BPS') => 'image/vnd.adobe.photoshop',
            str_starts_with($header, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") => 'application/vnd.ms-excel',
            str_starts_with($header, "PK\x03\x04"), str_starts_with($header, "PK\x05\x06"), str_starts_with($header, "PK\x07\x08") => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => null,
        };
    }
}
