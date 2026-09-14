<?php

namespace App\Services\KartuPelajar;

/**
 * Membangun kandidat kunci pencocokan per baris — HANYA menyiapkan kuncinya,
 * TIDAK melakukan pencocokan aktual (itu tugas mesin matching di tahap berikutnya).
 *
 * KENAPA DUA KUNCI (NISN + komposit), BUKAN NISN SAJA:
 * Dari data audit Prompt 1, NISN sering tidak bisa dipakai sendirian sebagai kunci:
 * - Di Excel master, NISN kerap kehilangan angka 0 di depan (tersimpan sebagai angka),
 *   sudah dikoreksi otomatis oleh NisnNormalizer tapi tetap ditandai warning.
 * - Di PDF Google Form, NISN sangat sering kosong/gagal terbaca karena kolom yang
 *   tumpang tindih saat ekstraksi (baris tsb ditandai needs_manual_review oleh AlamatNormalizer
 *   atau NisnNormalizer sendiri).
 * Kalau NISN dipaksa jadi satu-satunya kunci, semua baris di atas tidak akan pernah
 * ketemu pasangannya secara otomatis. Karena itu dibangun kunci KOMPOSIT (nama + tanggal
 * lahir + kelas, semua sudah dinormalisasi) sebagai fallback ketika NISN tidak tersedia/invalid.
 *
 * Kunci komposit ini murni untuk pencocokan EXACT (string sama persis) — bukan fuzzy
 * matching. Toleransi typo/beda ejaan antar 2 sumber adalah tugas mesin matching di
 * Prompt 3, yang sengaja belum dibangun di sini.
 */
class MatchingKeyBuilder
{
    /**
     * @return array{nisn: ?string, composite: ?string}
     */
    public function build(RowAuditResult $result): array
    {
        return [
            'nisn' => $this->buildNisnKey($result),
            'composite' => $this->buildCompositeKey($result),
        ];
    }

    private function buildNisnKey(RowAuditResult $result): ?string
    {
        $field = $result->fields['nisn'] ?? null;

        if ($field === null || $field->status === 'invalid' || $field->value === null) {
            return null;
        }

        return $field->value;
    }

    private function buildCompositeKey(RowAuditResult $result): ?string
    {
        $nama = $result->fieldValue('nama');
        $tanggalLahir = $result->fieldValue('tanggal_lahir');
        $kelas = $result->fieldValue('kelas');

        if (blank($nama) || blank($tanggalLahir) || blank($kelas)) {
            return null;
        }

        return mb_strtoupper(trim($nama)).'|'.$tanggalLahir.'|'.mb_strtoupper(trim($kelas));
    }
}
