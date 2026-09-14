<?php

namespace App\Services\KartuPelajar;

/**
 * Skor kemiripan nama berbasis Levenshtein distance, dinormalisasi ke rentang 0-1
 * (1 = identik). Dipakai untuk cek silang hasil match NISN dan untuk fuzzy matching
 * di MatchingEngine — bukan pengganti normalisasi nama (itu tugas NamaNormalizer).
 */
class NameSimilarity
{
    public static function score(string $a, string $b): float
    {
        $a = mb_strtoupper(trim($a));
        $b = mb_strtoupper(trim($b));

        if ($a === '' && $b === '') {
            return 1.0;
        }

        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0;
        }

        $maxLen = max(mb_strlen($a), mb_strlen($b));

        return 1 - (levenshtein($a, $b) / $maxLen);
    }
}
