<?php

namespace App\Services\KartuPelajar\Normalizers;

use App\Services\KartuPelajar\FieldResult;

class AlamatNormalizer
{
    public function normalize(?string $raw): FieldResult
    {
        $s = trim(preg_replace('/\s+/u', ' ', (string) $raw) ?? '');

        if ($s === '') {
            return FieldResult::warning(null, 'Alamat kosong');
        }

        if ($this->looksInterleaved($s)) {
            return FieldResult::invalid($s, 'Alamat mengandung pola tidak biasa (kemungkinan tercampur data lain atau kurang spasi antar kata) — perlu diperiksa manual');
        }

        return FieldResult::clean($s);
    }

    private function looksInterleaved(string $s): bool
    {
        $tokens = preg_split('/\s+/u', $s) ?: [];

        foreach ($tokens as $token) {
            if (mb_strlen($token) > 4 && preg_match('/\p{Ll}\p{Lu}/u', $token)) {
                return true;
            }

            if (mb_strlen($token) > 18 && preg_match('/^\p{L}+$/u', $token)) {
                return true;
            }

            if (preg_match('/[A-Za-z]\d[A-Za-z]/', $token) && ! preg_match('/^[A-Za-z]+\d+$/', $token)) {
                return true;
            }
        }

        return false;
    }
}
