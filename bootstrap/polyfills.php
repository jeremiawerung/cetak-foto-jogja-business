<?php

// mb_split() dihapus dari inti PHP sejak PHP 8.0, tapi Illuminate\Support\Str::studly()
// (dipakai di hampir semua resolusi "manager" driver Laravel, termasuk session - jadi
// kepanggil di HAMPIR SETIAP request) masih memanggilnya langsung. Polyfill bawaan
// symfony/polyfill-mbstring melewati fungsi ini kalau extension mbstring terdeteksi ada,
// meski mbstring versi PHP 8+ sudah tidak menyediakan mb_split - jadi tetap perlu
// disediakan manual di sini kalau hosting-nya kena kasus itu (mis. build mbstring
// tanpa oniguruma regex penuh).
if (! function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array|false
    {
        $result = preg_split('~'.$pattern.'~u', $string, $limit);

        return $result === false ? [$string] : $result;
    }
}
