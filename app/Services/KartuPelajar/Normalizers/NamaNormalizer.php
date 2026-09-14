<?php

namespace App\Services\KartuPelajar\Normalizers;

use App\Services\KartuPelajar\FieldResult;

class NamaNormalizer
{
    public function normalize(?string $raw): FieldResult
    {
        $trimmed = trim(preg_replace('/\s+/u', ' ', (string) $raw) ?? '');

        if ($trimmed === '') {
            return FieldResult::invalid(null, 'Nama kosong');
        }

        $titleCase = ucwords(mb_strtolower($trimmed), " \t\r\n\f\v-");

        if (! preg_match('/^[\p{L}\s\'.\-]+$/u', $titleCase)) {
            return FieldResult::warning($titleCase, 'Nama mengandung karakter tidak biasa, mohon diperiksa');
        }

        return FieldResult::clean($titleCase);
    }
}
