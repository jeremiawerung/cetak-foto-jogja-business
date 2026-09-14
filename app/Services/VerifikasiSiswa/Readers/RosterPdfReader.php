<?php

namespace App\Services\VerifikasiSiswa\Readers;

use App\Services\VerifikasiSiswa\AlamatComposer;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Baca roster dari PDF. Tabel lebar sering "terpotong" jadi beberapa kelompok halaman
 * ketika dicetak ke PDF (kolom-kolom berbeda muncul di rentang halaman berbeda, baris
 * yang sama diulang di tiap kelompok) — lihat sample nyata `profil kartu pelajar
 * 2026.pdf`: identitas (No/Nama/JK/NIS/NISN/TempatLahir) di beberapa halaman pertama,
 * lalu Tanggal Lahir+Agama, lalu Alamat+RT/RW, lalu Dusun/Kelurahan/Kecamatan, lalu
 * Rombel — semuanya SEJAJAR berdasarkan URUTAN POSISI baris, bukan nomor "No" yang
 * diulang (karena section selain identitas tidak mengulang nomor itu).
 *
 * Format sekolah lain bisa lebih sederhana (cuma section identitas saja) — reader ini
 * tetap jalan, section yang tidak ada dibiarkan kosong (field terkait jadi null).
 *
 * Hasilnya "best effort", bukan final — selalu ditinjau & bisa dikoreksi manual dulu
 * sebelum disimpan permanen (lihat alur upload roster).
 */
class RosterPdfReader
{
    /**
     * @return array{rows: list<array{no: ?string, nama: ?string, jenis_kelamin: ?string, nis: ?string, nisn: ?string, tempat_lahir: ?string, tanggal_lahir: ?string, alamat: ?string, agama: ?string, kelas: ?string}>, warnings: list<string>}
     */
    public function read(string $pdfPath): array
    {
        $binary = config('services.kartu_pelajar.pdftotext_path', 'pdftotext');

        $process = new Process([$binary, '-layout', $pdfPath, '-']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Gagal membaca PDF roster: '.$process->getErrorOutput());
        }

        return $this->parse($process->getOutput());
    }

    /**
     * Fungsi murni (tidak ada I/O) supaya bisa diuji langsung tanpa memanggil pdftotext.
     *
     * @return array{rows: list<array<string, ?string>>, warnings: list<string>}
     */
    public function parse(string $text): array
    {
        $pages = preg_split('/\f/', $text) ?: [$text];

        $sections = [
            'identitas' => [],
            'tanggal_agama' => [],
            'alamat_rtrw' => [],
            'wilayah' => [],
            'kelas' => [],
        ];

        $currentSection = null;

        foreach ($pages as $page) {
            $lines = preg_split('/\r\n|\r|\n/', $page) ?: [];

            foreach ($lines as $line) {
                $trimmed = trim($line);

                if ($trimmed === '') {
                    continue;
                }

                $detected = $this->detectSectionHeader($trimmed);

                if ($detected !== null) {
                    $currentSection = $detected;

                    continue;
                }

                if ($currentSection === null) {
                    continue;
                }

                $parsed = match ($currentSection) {
                    'identitas' => $this->parseIdentitasLine($trimmed),
                    'tanggal_agama' => $this->parseTanggalAgamaLine($trimmed),
                    'alamat_rtrw' => $this->parseAlamatRtRwLine($trimmed),
                    'wilayah' => $this->parseWilayahLine($trimmed),
                    'kelas' => $this->parseKelasLine($trimmed),
                    default => null,
                };

                if ($parsed !== null) {
                    $sections[$currentSection][] = $parsed;
                }
            }
        }

        return $this->zipSections($sections);
    }

    private function detectSectionHeader(string $line): ?string
    {
        if (preg_match('/\bnama\b/i', $line) && preg_match('/\bjk\b/i', $line) && preg_match('/\bnisn\b/i', $line)) {
            return 'identitas';
        }

        if (preg_match('/\btanggal\b/i', $line) && preg_match('/\blahir\b/i', $line) && preg_match('/\bagama\b/i', $line)) {
            return 'tanggal_agama';
        }

        if (preg_match('/\balamat\b/i', $line) && preg_match('/\brt\b/i', $line) && preg_match('/\brw\b/i', $line)) {
            return 'alamat_rtrw';
        }

        if (preg_match('/\bdusun\b/i', $line) && preg_match('/\bkelurahan\b/i', $line) && preg_match('/\bkecamatan\b/i', $line)) {
            return 'wilayah';
        }

        if (preg_match('/^rombel$/i', $line)) {
            return 'kelas';
        }

        return null;
    }

    /** @return array{no: ?string, nama: ?string, jenis_kelamin: ?string, nis: ?string, nisn: ?string, tempat_lahir: ?string}|null */
    private function parseIdentitasLine(string $line): ?array
    {
        if (! preg_match('/^(\d+)\s+(.+?)\s+([LP])\s+(\d+)\s+(\d+)\s+(\S.*)$/u', $line, $m)) {
            return null;
        }

        return [
            'no' => $m[1],
            'nama' => trim($m[2]),
            'jenis_kelamin' => $m[3],
            'nis' => $m[4],
            'nisn' => $m[5],
            'tempat_lahir' => trim($m[6]),
        ];
    }

    /** @return array{tanggal_lahir: ?string, agama: ?string}|null */
    private function parseTanggalAgamaLine(string $line): ?array
    {
        if (! preg_match('/^(\d{4}-\d{2}-\d{2})\s+(\S.*)$/u', $line, $m)) {
            return null;
        }

        return [
            'tanggal_lahir' => $m[1],
            'agama' => trim($m[2]),
        ];
    }

    /** @return array{alamat: ?string, rt: ?string, rw: ?string} */
    private function parseAlamatRtRwLine(string $line): array
    {
        if (preg_match('/^(.*\S)\s{2,}(\d{1,3})\s+(\d{1,3})\s*$/u', $line, $m)) {
            return ['alamat' => trim($m[1]), 'rt' => $m[2], 'rw' => $m[3]];
        }

        if (preg_match('/^(.*\S)\s{2,}(\d{1,3})\s*$/u', $line, $m)) {
            return ['alamat' => trim($m[1]), 'rt' => $m[2], 'rw' => null];
        }

        return ['alamat' => trim($line), 'rt' => null, 'rw' => null];
    }

    /** @return array{dusun: ?string, kelurahan: ?string, kecamatan: ?string} */
    private function parseWilayahLine(string $line): array
    {
        if (preg_match('/^(.*?)\s{2,}(.*?)\s{2,}(\S.*)$/u', $line, $m)) {
            return ['dusun' => trim($m[1]) ?: null, 'kelurahan' => trim($m[2]) ?: null, 'kecamatan' => trim($m[3])];
        }

        if (preg_match('/^(.*?)\s{2,}(\S.*)$/u', $line, $m)) {
            return ['dusun' => null, 'kelurahan' => trim($m[1]) ?: null, 'kecamatan' => trim($m[2])];
        }

        return ['dusun' => null, 'kelurahan' => null, 'kecamatan' => trim($line)];
    }

    /** @return array{kelas: string} */
    private function parseKelasLine(string $line): array
    {
        return ['kelas' => trim($line)];
    }

    /**
     * @param  array<string, list<array<string, ?string>>>  $sections
     * @return array{rows: list<array<string, ?string>>, warnings: list<string>}
     */
    private function zipSections(array $sections): array
    {
        $warnings = [];
        $n = count($sections['identitas']);

        if ($n === 0) {
            return ['rows' => [], 'warnings' => ['Tidak ditemukan baris identitas (No/Nama/JK/NIS/NISN) di file ini.']];
        }

        foreach (['tanggal_agama', 'alamat_rtrw', 'wilayah', 'kelas'] as $key) {
            $count = count($sections[$key]);

            if ($count > 0 && $count !== $n) {
                $warnings[] = "Section '{$key}' punya {$count} baris, tidak sama dengan {$n} baris identitas — diabaikan, kolom terkait dikosongkan (perlu diisi manual di layar tinjau).";
                $sections[$key] = [];
            }
        }

        $rows = [];

        for ($i = 0; $i < $n; $i++) {
            $identitas = $sections['identitas'][$i];
            $tanggalAgama = $sections['tanggal_agama'][$i] ?? null;
            $alamatRtRw = $sections['alamat_rtrw'][$i] ?? null;
            $wilayah = $sections['wilayah'][$i] ?? null;
            $kelas = $sections['kelas'][$i] ?? null;

            $rows[] = [
                'no' => $identitas['no'],
                'nama' => $identitas['nama'],
                'jenis_kelamin' => $identitas['jenis_kelamin'],
                'nis' => $identitas['nis'],
                'nisn' => $identitas['nisn'],
                'tempat_lahir' => $identitas['tempat_lahir'],
                'tanggal_lahir' => $tanggalAgama['tanggal_lahir'] ?? null,
                'agama' => $tanggalAgama['agama'] ?? null,
                'alamat' => AlamatComposer::compose(
                    $alamatRtRw['alamat'] ?? null,
                    $alamatRtRw['rt'] ?? null,
                    $alamatRtRw['rw'] ?? null,
                    $wilayah['kelurahan'] ?? null,
                    $wilayah['kecamatan'] ?? null,
                ),
                'kelas' => $kelas['kelas'] ?? null,
            ];
        }

        return ['rows' => $rows, 'warnings' => $warnings];
    }
}
