<?php

namespace App\Services\KartuPelajar\Normalizers;

use App\Services\KartuPelajar\FieldResult;

class NisnNormalizer
{
    public function normalize(?string $raw): FieldResult
    {
        $s = trim((string) $raw);

        if ($s === '') {
            return FieldResult::invalid(null, 'NISN kosong');
        }

        $fixedOcr = preg_replace('/[Oo]/', '0', $s);
        $ocrCorrected = $fixedOcr !== $s;
        $digitsOnly = preg_replace('/\D/', '', $fixedOcr ?? $s) ?? '';

        // NISN yang tersimpan sebagai angka di Excel sering kehilangan 1 digit "0" di depan.
        $paddedLeadingZero = false;
        if (strlen($digitsOnly) === 9) {
            $digitsOnly = '0'.$digitsOnly;
            $paddedLeadingZero = true;
        }

        if (strlen($digitsOnly) !== 10) {
            return FieldResult::invalid($s, 'NISN harus terdiri dari 10 digit angka, ditemukan '.strlen($digitsOnly).' digit');
        }

        if ($ocrCorrected) {
            return FieldResult::warning($digitsOnly, 'NISN terkoreksi otomatis (huruf O dibaca sebagai angka 0), mohon diverifikasi');
        }

        if ($paddedLeadingZero) {
            return FieldResult::warning($digitsOnly, 'NISN terkoreksi otomatis (kemungkinan angka 0 di depan hilang karena tersimpan sebagai angka di Excel), mohon diverifikasi');
        }

        return FieldResult::clean($digitsOnly);
    }
}
