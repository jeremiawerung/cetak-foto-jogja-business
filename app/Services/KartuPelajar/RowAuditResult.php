<?php

namespace App\Services\KartuPelajar;

final class RowAuditResult
{
    /**
     * @param  array<string, FieldResult>  $fields
     * @param  list<string>  $reasons
     */
    public function __construct(
        public readonly string $source,
        public readonly int $rowNumber,
        public readonly array $fields,
        public string $status,
        public array $reasons,
        public ?int $duplicateOfRow = null,
        // Field pelengkap untuk ekspor (Prompt 5) - apa adanya, tidak melalui audit/normalisasi.
        public readonly ?string $tempatLahir = null,
        public readonly ?string $agama = null,
        public readonly ?string $jenisKelamin = null,
    ) {}

    public function fieldValue(string $key): ?string
    {
        return $this->fields[$key]->value ?? null;
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toCsvRow(): array
    {
        return [
            'sumber' => $this->source,
            'baris' => $this->rowNumber,
            'status' => $this->status,
            'nama' => $this->fieldValue('nama'),
            'kelas' => $this->fieldValue('kelas'),
            'tanggal_lahir' => $this->fieldValue('tanggal_lahir'),
            'nis' => $this->fieldValue('nis'),
            'nisn' => $this->fieldValue('nisn'),
            'alamat' => $this->fieldValue('alamat'),
            'duplikat_dari_baris' => $this->duplicateOfRow,
            'catatan' => implode(' | ', $this->reasons),
        ];
    }
}
