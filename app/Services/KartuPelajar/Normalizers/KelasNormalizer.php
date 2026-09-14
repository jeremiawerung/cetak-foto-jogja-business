<?php

namespace App\Services\KartuPelajar\Normalizers;

use App\Services\KartuPelajar\FieldResult;

class KelasNormalizer
{
    private const ROMAN_TO_DECIMAL = [
        'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6,
        'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12,
    ];

    public function normalize(?string $raw): FieldResult
    {
        $s = strtoupper(trim((string) $raw));

        if ($s === '') {
            return FieldResult::invalid(null, 'Kelas kosong');
        }

        if (preg_match('/^(.+?)\s+([A-Z])$/u', $s, $m)) {
            $grade = $this->resolveGrade(str_replace(' ', '', $m[1]));
            if ($grade !== null) {
                return FieldResult::clean($grade.$m[2]);
            }
        }

        $compact = str_replace(' ', '', $s);
        if (preg_match('/^(.+)([A-Z])$/u', $compact, $m)) {
            $grade = $this->resolveGrade($m[1]);
            if ($grade !== null) {
                return FieldResult::clean($grade.$m[2]);
            }
        }

        return FieldResult::invalid($s, 'Format kelas tidak dikenali');
    }

    private function resolveGrade(string $token): ?int
    {
        if (ctype_digit($token)) {
            $n = (int) $token;

            return ($n >= 1 && $n <= 12) ? $n : null;
        }

        return self::ROMAN_TO_DECIMAL[$token] ?? null;
    }
}
