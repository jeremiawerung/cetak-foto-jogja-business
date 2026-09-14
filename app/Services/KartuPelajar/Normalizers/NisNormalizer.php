<?php

namespace App\Services\KartuPelajar\Normalizers;

use App\Services\KartuPelajar\FieldResult;

class NisNormalizer
{
    public function normalize(?string $raw): FieldResult
    {
        $s = trim((string) $raw);

        if ($s === '') {
            return FieldResult::invalid(null, 'NIS kosong');
        }

        if (! ctype_digit($s)) {
            return FieldResult::invalid($s, 'NIS harus berupa angka');
        }

        return FieldResult::clean($s);
    }
}
