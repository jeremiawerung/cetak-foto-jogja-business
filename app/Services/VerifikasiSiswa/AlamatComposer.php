<?php

namespace App\Services\VerifikasiSiswa;

/**
 * Gabungkan komponen alamat (alamat utama + RT/RW + kecamatan) jadi 1 string akhir,
 * mengikuti pola yang sudah dipakai user di prompt AI-nya: hapus kata "RT"/"RW" dari
 * teks, angka RT/RW di-pad 3 digit lalu digabung "RTT/RWW", hapus "DIY"/"Daerah
 * Istimewa Yogyakarta", tambahkan "Yogyakarta" di akhir kalau belum ada.
 *
 * Hasilnya tetap best-effort — bisa diedit manual di layar tinjau roster kalau kurang pas.
 */
class AlamatComposer
{
    public static function compose(
        ?string $alamat,
        ?string $rt = null,
        ?string $rw = null,
        ?string $kelurahan = null,
        ?string $kecamatan = null,
    ): ?string {
        $base = trim((string) $alamat);
        $base = preg_replace('/\bRT\.?\s*\d*\b/i', '', $base) ?? $base;
        $base = preg_replace('/\bRW\.?\s*\d*\b/i', '', $base) ?? $base;
        $base = preg_replace('/\b(DIY|Daerah\s+Istimewa\s+Yogyakarta)\b/i', '', $base) ?? $base;
        $base = preg_replace('/\s{2,}/', ' ', $base) ?? $base;
        $base = trim($base, " \t\n\r\0\x0B,/");

        $rtRw = self::composeRtRw($rt, $rw);
        $kelurahanBersih = self::stripPrefix($kelurahan, ['Desa/Kel.', 'Desa/Kel', 'Kel.', 'Kel']);
        $kecamatanBersih = self::stripPrefix($kecamatan, ['Kec.', 'Kec']);

        $parts = array_filter([$base, $rtRw, $kelurahanBersih, $kecamatanBersih], fn ($v) => $v !== null && $v !== '');

        if ($parts === []) {
            return null;
        }

        $result = implode(' ', $parts);

        if (! preg_match('/yogyakarta/i', $result)) {
            $result .= ' Yogyakarta';
        }

        return trim(preg_replace('/\s{2,}/', ' ', $result) ?? $result);
    }

    private static function composeRtRw(?string $rt, ?string $rw): ?string
    {
        $rt = self::padThree($rt);
        $rw = self::padThree($rw);

        if ($rt === null && $rw === null) {
            return null;
        }

        return ($rt ?? '000').'/'.($rw ?? '000');
    }

    private static function padThree(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        return $digits === '' ? null : str_pad($digits, 3, '0', STR_PAD_LEFT);
    }

    private static function stripPrefix(?string $value, array $prefixes): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach ($prefixes as $prefix) {
            if (stripos($value, $prefix) === 0) {
                $value = trim(substr($value, strlen($prefix)));

                break;
            }
        }

        return $value === '' ? null : $value;
    }
}
