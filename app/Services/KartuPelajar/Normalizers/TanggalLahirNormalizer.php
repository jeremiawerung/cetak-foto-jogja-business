<?php

namespace App\Services\KartuPelajar\Normalizers;

use App\Services\KartuPelajar\FieldResult;

class TanggalLahirNormalizer
{
    /** Ejaan yang dikenali tanpa peringatan: baku Indonesia + alias bahasa Inggris + ejaan lama yang lazim. */
    private const RECOGNIZED_MONTHS = [
        'januari' => 1, 'january' => 1,
        'februari' => 2, 'february' => 2,
        'maret' => 3, 'march' => 3,
        'april' => 4,
        'mei' => 5, 'may' => 5,
        'juni' => 6, 'june' => 6,
        'juli' => 7, 'july' => 7,
        'agustus' => 8, 'august' => 8,
        'september' => 9,
        'oktober' => 10, 'october' => 10,
        'november' => 11, 'nopember' => 11,
        'desember' => 12, 'december' => 12,
    ];

    /** Ejaan yang diterima tapi kemungkinan typo — nilainya dipakai, tapi baris ditandai warning. */
    private const TYPO_MONTHS = [
        'mey' => 5,
    ];

    public function normalize(?string $raw): FieldResult
    {
        $s = str_replace(',', ' ', (string) $raw);
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? '');

        if ($s === '') {
            return FieldResult::invalid(null, 'Tanggal lahir kosong');
        }

        $corrected = false;

        $fixedOcr = preg_replace('/\b[Oo](\d{1,2})\b/', '0$1', $s);
        if ($fixedOcr !== null && $fixedOcr !== $s) {
            $corrected = true;
            $s = $fixedOcr;
        }

        if (preg_match('/(\d{1,2})\s*([A-Za-z]+)\s*(\d{4})/u', $s, $m)) {
            $day = (int) $m[1];
            $year = (int) $m[3];
            $monthWord = mb_strtolower($m[2]);

            $month = self::RECOGNIZED_MONTHS[$monthWord] ?? null;
            if ($month === null) {
                $month = self::TYPO_MONTHS[$monthWord] ?? null;
                if ($month !== null) {
                    $corrected = true;
                }
            }

            if ($month === null) {
                return FieldResult::invalid(null, "Nama bulan tidak dikenali: \"{$m[2]}\"");
            }

            if (! checkdate($month, $day, $year)) {
                return FieldResult::invalid(null, "Tanggal tidak valid: {$day}/{$month}/{$year}");
            }

            $iso = sprintf('%04d-%02d-%02d', $year, $month, $day);

            if ($corrected) {
                return FieldResult::warning($iso, 'Tanggal lahir terkoreksi otomatis (typo/format tidak baku), mohon diverifikasi');
            }

            return FieldResult::clean($iso);
        }

        if (preg_match('/([A-Za-z]+)\s*(\d{4})/u', $s, $m)) {
            $monthWord = mb_strtolower($m[1]);
            if (isset(self::RECOGNIZED_MONTHS[$monthWord]) || isset(self::TYPO_MONTHS[$monthWord])) {
                return FieldResult::warning(null, 'Tanggal lahir tidak lengkap (hanya bulan dan tahun tersedia)');
            }
        }

        return FieldResult::invalid(null, 'Tanggal lahir tidak bisa diparse');
    }

    /**
     * Ambil bagian TEMPAT lahir dari string gabungan "tempat + tanggal lahir" —
     * pelengkap untuk format ekspor (kolom TTL), bukan bagian dari audit/normalisasi
     * tanggalnya sendiri. Sekadar apa-adanya (trim), tidak divalidasi/dinormalisasi.
     */
    public function extractTempatLahir(?string $raw): ?string
    {
        $s = str_replace(',', ' ', (string) $raw);
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? '');

        if ($s === '') {
            return null;
        }

        if (! preg_match('/\d{1,2}\s*[A-Za-z]+\s*\d{4}/u', $s, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $tempat = trim(substr($s, 0, $m[0][1]));

        return $tempat !== '' ? $tempat : null;
    }
}
