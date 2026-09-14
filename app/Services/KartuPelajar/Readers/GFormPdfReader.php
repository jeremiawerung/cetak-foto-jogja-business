<?php

namespace App\Services\KartuPelajar\Readers;

use App\Services\KartuPelajar\Normalizers\TanggalLahirNormalizer;
use RuntimeException;
use Symfony\Component\Process\Process;

class GFormPdfReader
{
    private const AGAMA_PATTERN = '/\b(islam|kristen|katolik|katholik|protestan|hindu|buddha|budha|konghucu|khonghucu)\b/iu';

    /** Jeda >=2 spasi menandai batas kolom di hasil `pdftotext -layout` (beda dari 1 spasi antar-kata). */
    private const COLUMN_GAP_PATTERN = '/^(.*?)\s{2,}(\S.*)$/su';

    public function __construct(
        private readonly TanggalLahirNormalizer $tanggalLahirNormalizer = new TanggalLahirNormalizer,
    ) {}

    /**
     * Shell ke `pdftotext -layout`, lalu proses tiap baris lewat parseLine().
     *
     * @return list<array{row_number: int, timestamp: string, kelas: ?string, nama: ?string, tanggal_lahir: ?string, tempat_lahir: ?string, alamat: ?string, nisn: ?string, agama: ?string}>
     */
    public function read(string $pdfPath): array
    {
        $binary = config('services.kartu_pelajar.pdftotext_path', 'pdftotext');

        $process = new Process([$binary, '-layout', $pdfPath, '-']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Gagal membaca PDF Google Form: '.$process->getErrorOutput());
        }

        $lines = preg_split('/\r\n|\r|\n/', $process->getOutput()) ?: [];

        $rows = [];
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;

            if (trim($line) === '') {
                continue;
            }

            $parsed = $this->parseLine($line, $lineNumber);

            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }

        return $rows;
    }

    /**
     * Fungsi murni (tidak ada I/O) supaya bisa diuji langsung tanpa memanggil pdftotext.
     * Setiap field dicari lewat bentuk kontennya sendiri (bukan asumsi posisi kolom tetap),
     * supaya baris yang kolom tengahnya kosong tidak membuat field lain salah geser.
     *
     * @return array{row_number: int, timestamp: string, kelas: ?string, nama: ?string, tanggal_lahir: ?string, tempat_lahir: ?string, alamat: ?string, nisn: ?string, agama: ?string}|null
     */
    public function parseLine(string $line, int $lineNumber): ?array
    {
        $trimmed = trim($line);

        if (! preg_match('/^(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\s*(.*)$/u', $trimmed, $m)) {
            return null;
        }

        $timestamp = $this->normalizeTimestamp($m[1]);
        $rest = $m[2];

        $kelas = null;

        if (preg_match('/^\s*([IVX]+\s+[A-Za-z])\b/u', $rest, $km)) {
            $kelas = preg_replace('/\s+/u', ' ', trim($km[1]));
            $rest = trim(substr($rest, strlen($km[0])));
        }

        if (trim($rest) === '') {
            return [
                'row_number' => $lineNumber,
                'timestamp' => $timestamp,
                'kelas' => $kelas,
                'nama' => null,
                'tanggal_lahir' => null,
                'tempat_lahir' => null,
                'alamat' => null,
                'nisn' => null,
                'agama' => null,
            ];
        }

        [$nama, $tanggalLahir, $alamat, $nisn, $agama] = $this->splitRemainder($rest);

        return [
            'row_number' => $lineNumber,
            'timestamp' => $timestamp,
            'kelas' => $kelas,
            'nama' => $nama,
            'tanggal_lahir' => $tanggalLahir,
            'tempat_lahir' => $this->tanggalLahirNormalizer->extractTempatLahir($tanggalLahir),
            'alamat' => $alamat,
            'nisn' => $nisn,
            'agama' => $agama,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string, 4: ?string} [nama, tanggal_lahir, alamat, nisn, agama]
     */
    private function splitRemainder(string $rest): array
    {
        $agama = null;

        if (preg_match(self::AGAMA_PATTERN, $rest, $agamaMatch, PREG_OFFSET_CAPTURE)) {
            $agama = $agamaMatch[0][0];
            $beforeAgama = substr($rest, 0, $agamaMatch[0][1]);
            $afterAgama = substr($rest, $agamaMatch[0][1] + \strlen($agamaMatch[0][0]));
        } else {
            $beforeAgama = $rest;
            $afterAgama = '';
        }

        // NAMA dan TEMPAT-TANGGAL-LAHIR adalah 2 kolom terpisah — pisahkan lewat jeda kolom,
        // lalu serahkan gabungan "tempat + tanggal" apa adanya ke TanggalLahirNormalizer
        // (normalizer itu sendiri yang akan mengabaikan nama tempatnya).
        if (preg_match(self::COLUMN_GAP_PATTERN, trim($beforeAgama), $colSplit)) {
            $nama = trim($colSplit[1]);
            $tanggalLahir = trim($colSplit[2]);
        } else {
            $nama = trim($beforeAgama);
            $tanggalLahir = null;
        }

        if ($tanggalLahir === '') {
            $tanggalLahir = null;
        }

        $nisn = null;

        if (preg_match_all('/[0-9Oo]{4,12}/u', $afterAgama, $numMatches, PREG_OFFSET_CAPTURE) && $numMatches[0] !== []) {
            $last = end($numMatches[0]);
            $nisn = $last[0];
            $alamat = trim(substr($afterAgama, 0, $last[1]));
        } else {
            $alamat = trim($afterAgama);
        }

        return [
            $nama !== '' ? $nama : null,
            $tanggalLahir,
            $alamat !== '' ? $alamat : null,
            $nisn,
            $agama,
        ];
    }

    private function normalizeTimestamp(string $raw): string
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}:\d{2}:\d{2})$/', trim($raw), $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]} {$m[4]}";
        }

        return trim($raw);
    }
}
