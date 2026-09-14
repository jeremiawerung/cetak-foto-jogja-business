<?php

namespace App\Services\VerifikasiSiswa;

use App\Models\VerifikasiSiswaFieldDefinition;
use Illuminate\Support\Collection;

/**
 * Cocokkan nama kolom (header Excel/Google Form) ke field key, berdasarkan daftar
 * ATURAN per field yang URUTANNYA menentukan prioritas — dipakai bersama oleh
 * RosterExcelReader & GFormSheetReader supaya field yang tidak ada di sebuah sekolah
 * (mis. tidak ada kolom Agama) otomatis dilewati tanpa menggeser field lain, dan field
 * custom (mis. "Nomor Sekolah") bisa dikenali kalau proyek mendaftarkan kata kuncinya.
 *
 * 1 field bisa punya beberapa ATURAN (OR — cukup salah satu cocok), dan 1 aturan bisa
 * berisi beberapa kata (AND — semua kata itu harus ada di header, dalam urutan apa
 * pun) — ini supaya header gabungan seperti "Tempat, Tanggal Lahir" (ada tanda baca
 * di antara 2 kata) tetap kecocok sebagai "tanggal_lahir" (aturan: ["tanggal","lahir"]).
 *
 * Urutan definisi PENTING: field dengan kata kunci pendek/rawan tabrakan substring
 * (mis. "jenis_kelamin" - "jk"/"jenis"+"kelamin") harus dicek SEBELUM field yang kata
 * kuncinya bisa jadi substring di dalamnya (mis. "nis", karena "jenis kelamin"
 * mengandung "nis").
 */
class FieldHeaderMatcher
{
    /**
     * @param  list<array{key: string, aturan: list<list<string>>}>  $definitions  urutan = prioritas
     */
    public function matchHeader(string $header, array $definitions): ?string
    {
        $h = mb_strtolower(trim($header));

        if ($h === '') {
            return null;
        }

        foreach ($definitions as $definition) {
            foreach ($definition['aturan'] as $kataKunciGrup) {
                $cocok = true;

                foreach ($kataKunciGrup as $kataKunci) {
                    if ($h !== $kataKunci && ! str_contains($h, $kataKunci)) {
                        $cocok = false;

                        break;
                    }
                }

                if ($cocok) {
                    return $definition['key'];
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $headers  nama kolom berurutan (index 0 = kolom pertama)
     * @param  list<array{key: string, aturan: list<list<string>>}>  $definitions
     * @return array<string, int> field key => index kolom (0-based)
     */
    public function buildColumnMap(array $headers, array $definitions): array
    {
        $map = [];

        foreach ($headers as $index => $header) {
            $key = $this->matchHeader((string) $header, $definitions);

            if ($key !== null && ! isset($map[$key])) {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    /**
     * Susun daftar definisi (field data proyek + NIS/NISN yang selalu ada tapi bukan
     * field_definition biasa karena cuma kunci pencocokan, tidak pernah digabung/
     * ditampilkan) dalam urutan prioritas yang aman dari tabrakan substring:
     * jenis_kelamin dulu (kalau ada) -> NISN -> NIS -> sisanya (nama, kelas, dst)
     * sesuai urutan tersimpan.
     *
     * @param  Collection<int, VerifikasiSiswaFieldDefinition>  $fieldDefinitions
     * @return list<array{key: string, aturan: list<list<string>>}>
     */
    public function definitionsForReading(Collection $fieldDefinitions): array
    {
        $jenisKelamin = $fieldDefinitions->firstWhere('key', 'jenis_kelamin');
        $sisanya = $fieldDefinitions->reject(fn ($f) => $f->key === 'jenis_kelamin');

        $definitions = [];

        if ($jenisKelamin !== null) {
            $definitions[] = ['key' => 'jenis_kelamin', 'aturan' => $jenisKelamin->kata_kunci_header ?? [['jk'], ['jenis', 'kelamin']]];
        }

        $definitions[] = ['key' => 'nisn', 'aturan' => [['nisn']]];
        $definitions[] = ['key' => 'nis', 'aturan' => [['nis']]];

        foreach ($sisanya as $field) {
            $definitions[] = ['key' => $field->key, 'aturan' => $field->kata_kunci_header ?? [[$field->key]]];
        }

        return $definitions;
    }
}
