<?php

namespace App\Services\KartuPelajar;

use App\Services\KartuPelajar\Normalizers\AlamatNormalizer;
use App\Services\KartuPelajar\Normalizers\KelasNormalizer;
use App\Services\KartuPelajar\Normalizers\NamaNormalizer;
use App\Services\KartuPelajar\Normalizers\NisnNormalizer;
use App\Services\KartuPelajar\Normalizers\NisNormalizer;
use App\Services\KartuPelajar\Normalizers\TanggalLahirNormalizer;

class RowAuditor
{
    /** Urutan tetap supaya output CSV/laporan stabil dan mudah dibandingkan antar-run. */
    private const FIELD_ORDER = ['nama', 'kelas', 'tanggal_lahir', 'nis', 'nisn', 'alamat'];

    public function __construct(
        private readonly NamaNormalizer $namaNormalizer = new NamaNormalizer,
        private readonly KelasNormalizer $kelasNormalizer = new KelasNormalizer,
        private readonly TanggalLahirNormalizer $tanggalLahirNormalizer = new TanggalLahirNormalizer,
        private readonly NisNormalizer $nisNormalizer = new NisNormalizer,
        private readonly NisnNormalizer $nisnNormalizer = new NisnNormalizer,
        private readonly AlamatNormalizer $alamatNormalizer = new AlamatNormalizer,
    ) {}

    /**
     * @param  array<string, string|null>  $rawFields  hanya berisi key field yang relevan untuk sumber data ini
     */
    public function auditRow(string $source, int $rowNumber, array $rawFields): RowAuditResult
    {
        $fields = [];
        $reasons = [];
        $maxSeverity = 0;

        foreach (self::FIELD_ORDER as $key) {
            if (! array_key_exists($key, $rawFields)) {
                continue;
            }

            $result = $this->normalizerFor($key)->normalize($rawFields[$key]);
            $fields[$key] = $result;
            $maxSeverity = max($maxSeverity, $result->severity());

            if ($result->note !== null) {
                $reasons[] = $result->note;
            }
        }

        if (isset($fields['nis'], $fields['nisn'])
            && $fields['nis']->value !== null
            && $fields['nisn']->value !== null
            && $fields['nis']->value === $fields['nisn']->value
        ) {
            $maxSeverity = max($maxSeverity, 2);
            $reasons[] = 'NIS dan NISN memiliki nilai yang sama persis — kemungkinan tertukar';
        }

        $status = match (true) {
            $maxSeverity >= 2 => 'needs_manual_review',
            $maxSeverity === 1 => 'normalized_with_warning',
            default => 'clean',
        };

        return new RowAuditResult(
            $source,
            $rowNumber,
            $fields,
            $status,
            $reasons,
            tempatLahir: $this->trimmedOrNull($rawFields['tempat_lahir'] ?? null),
            agama: $this->trimmedOrNull($rawFields['agama'] ?? null),
            jenisKelamin: $this->trimmedOrNull($rawFields['jenis_kelamin'] ?? null),
        );
    }

    private function trimmedOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function normalizerFor(string $key): object
    {
        return match ($key) {
            'nama' => $this->namaNormalizer,
            'kelas' => $this->kelasNormalizer,
            'tanggal_lahir' => $this->tanggalLahirNormalizer,
            'nis' => $this->nisNormalizer,
            'nisn' => $this->nisnNormalizer,
            'alamat' => $this->alamatNormalizer,
        };
    }
}
