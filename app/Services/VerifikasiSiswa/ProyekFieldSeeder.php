<?php

namespace App\Services\VerifikasiSiswa;

use App\Models\VerifikasiSiswaProyek;

/**
 * Sumber tunggal untuk field & kolom ekspor DEFAULT — dipakai saat proyek baru dibuat
 * (VerifikasiSiswaProyekController::store()) MAUPUN saat migrasi proyek lama ke skema
 * field dinamis (command verifikasi-siswa:migrasi-field-dinamis). Nilainya sengaja
 * meniru PERSIS perilaku hardcode yang ada sebelum field dinamis dibuat, supaya proyek
 * yang tidak pernah diubah manual tetap berjalan sama seperti sebelumnya.
 */
class ProyekFieldSeeder
{
    /**
     * Tidak melakukan apa-apa kalau proyek sudah punya field_definitions (idempotent —
     * aman dipanggil ulang, mis. dari command migrasi).
     */
    public function seedDefaults(VerifikasiSiswaProyek $proyek): void
    {
        if ($proyek->fieldDefinitions()->exists()) {
            return;
        }

        foreach ($this->defaultFieldDefinitions() as $definition) {
            $proyek->fieldDefinitions()->create($definition);
        }

        foreach ($this->defaultExportColumns() as $column) {
            $proyek->exportColumns()->create($column);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function defaultFieldDefinitions(): array
    {
        return [
            ['key' => 'nama', 'label' => 'Nama', 'tipe' => 'text', 'is_core' => true, 'sumber_utama' => 'roster', 'urutan' => 1, 'kata_kunci_header' => [['nama']]],
            ['key' => 'kelas', 'label' => 'Kelas', 'tipe' => 'text', 'is_core' => true, 'sumber_utama' => 'roster', 'urutan' => 2, 'kata_kunci_header' => [['rombel'], ['kelas']]],
            ['key' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'tipe' => 'text', 'is_core' => false, 'sumber_utama' => 'roster', 'urutan' => 3, 'kata_kunci_header' => [['jk'], ['jenis', 'kelamin']]],
            ['key' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'tipe' => 'date', 'is_core' => false, 'sumber_utama' => 'roster', 'urutan' => 4, 'kata_kunci_header' => [['tanggal', 'lahir']]],
            ['key' => 'tempat_lahir', 'label' => 'Tempat Lahir', 'tipe' => 'text', 'is_core' => false, 'sumber_utama' => 'roster', 'urutan' => 5, 'kata_kunci_header' => [['tempat', 'lahir']]],
            ['key' => 'agama', 'label' => 'Agama', 'tipe' => 'text', 'is_core' => false, 'sumber_utama' => 'roster', 'urutan' => 6, 'kata_kunci_header' => [['agama']]],
            ['key' => 'alamat', 'label' => 'Alamat', 'tipe' => 'text', 'is_core' => false, 'sumber_utama' => 'form', 'urutan' => 7, 'kata_kunci_header' => [['alamat']]],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function defaultExportColumns(): array
    {
        return [
            ['urutan' => 1, 'label' => 'NAMA', 'sumber_tipe' => 'field', 'field_key' => 'nama'],
            ['urutan' => 2, 'label' => 'TTL', 'sumber_tipe' => 'ttl', 'field_key' => null],
            ['urutan' => 3, 'label' => 'ALAMAT', 'sumber_tipe' => 'field', 'field_key' => 'alamat'],
            ['urutan' => 4, 'label' => 'JK', 'sumber_tipe' => 'field', 'field_key' => 'jenis_kelamin'],
            ['urutan' => 5, 'label' => 'AGAMA', 'sumber_tipe' => 'field', 'field_key' => 'agama'],
            ['urutan' => 6, 'label' => 'NIS', 'sumber_tipe' => 'field', 'field_key' => 'nis'],
            ['urutan' => 7, 'label' => 'NISN', 'sumber_tipe' => 'field', 'field_key' => 'nisn'],
            ['urutan' => 8, 'label' => 'ID_FILE', 'sumber_tipe' => 'id_file', 'field_key' => null],
        ];
    }
}
