<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaFieldDefinition;
use App\Models\VerifikasiSiswaProyek;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\Normalizers\NisnNormalizer;
use App\Services\KartuPelajar\Normalizers\NisNormalizer;
use App\Services\VerifikasiSiswa\ActivityLogger;
use App\Services\VerifikasiSiswa\Readers\RosterExcelReader;
use App\Services\VerifikasiSiswa\Readers\RosterPdfReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Alur upload roster: parse (PDF/Excel) -> simpan hasil parse ke SESSION (belum
 * permanen) -> admin tinjau & koreksi di layar tinjau -> simpan -> baru masuk permanen
 * ke tabel verifikasi_siswas. Format roster bervariasi antar sekolah dan parser tidak
 * dijamin 100% benar, jadi langkah tinjau ini WAJIB, bukan opsional.
 *
 * Field APA SAJA yang dibaca/disimpan ditentukan oleh field_definitions milik proyek
 * (bisa beda-beda per sekolah, termasuk field custom) — bukan daftar tetap lagi.
 * RosterPdfReader tetap menghasilkan 5 field standar saja (keterbatasan yang disengaja,
 * lihat komentar di kelas itu); field custom dari PDF perlu diisi manual di layar tinjau.
 *
 * Upload roster yang KEDUA kali (dst) untuk proyek yang sama TIDAK menimpa siswa yang
 * sudah ada — cuma menambah siswa baru & mengisi field yang masih kosong. Ini supaya
 * upload ulang (sengaja atau tidak sengaja) tidak menghapus data yang sudah digabung
 * dari Google Form (lihat SiswaMergeApplier) untuk siswa yang sudah "Terisi".
 */
class VerifikasiSiswaRosterController extends Controller
{
    public function upload(Request $request, VerifikasiSiswaProyek $proyek, RosterExcelReader $excelReader, RosterPdfReader $pdfReader): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,pdf', 'max:20480'],
        ]);

        $file = $request->file('file');
        $extension = mb_strtolower($file->getClientOriginalExtension());

        try {
            if ($extension === 'pdf') {
                $result = $pdfReader->read($file->getRealPath());
            } else {
                $result = ['rows' => $excelReader->read($file->getRealPath(), $proyek->fieldDefinitions), 'warnings' => []];
            }
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => 'Gagal membaca file: '.$e->getMessage()]);
        }

        if ($result['rows'] === []) {
            $warningText = $result['warnings'] !== [] ? implode(' ', $result['warnings']) : 'Tidak ada baris yang berhasil dibaca dari file ini.';

            return back()->withErrors(['file' => $warningText]);
        }

        session(["verifikasi_siswa.roster_review.{$proyek->id}" => $result]);

        return redirect()->route('internal.verifikasi-siswa.roster.tinjau', $proyek);
    }

    public function tinjau(VerifikasiSiswaProyek $proyek): View|RedirectResponse
    {
        $data = session("verifikasi_siswa.roster_review.{$proyek->id}");

        if ($data === null) {
            return redirect()->route('internal.verifikasi-siswa.show', $proyek)
                ->withErrors(['roster' => 'Belum ada hasil upload untuk ditinjau, upload file roster dulu.']);
        }

        return view('internal.verifikasi-siswa.roster.tinjau', [
            'proyek' => $proyek,
            'rows' => $data['rows'],
            'warnings' => $data['warnings'],
            'fieldDefinitions' => $proyek->fieldDefinitions,
        ]);
    }

    public function simpan(Request $request, VerifikasiSiswaProyek $proyek, ActivityLogger $logger): RedirectResponse
    {
        $data = session("verifikasi_siswa.roster_review.{$proyek->id}");

        if ($data === null) {
            return redirect()->route('internal.verifikasi-siswa.show', $proyek)
                ->withErrors(['roster' => 'Sesi tinjau sudah kedaluwarsa, upload ulang file roster.']);
        }

        $request->validate(['data' => ['required', 'string']]);

        // Baris dikirim sebagai 1 JSON (bukan array field rows[i][field] terpisah) —
        // roster 1 sekolah bisa ratusan baris x banyak field, gampang lewat batas
        // max_input_vars PHP (default 1000) kalau dikirim sebagai field POST biasa,
        // dan PHP diam-diam membuang sisanya tanpa error kalau itu terjadi.
        $rows = json_decode($request->string('data')->toString(), true);

        if (! is_array($rows)) {
            return back()->withErrors(['roster' => 'Data yang dikirim tidak valid, coba upload ulang.']);
        }

        $nisNormalizer = new NisNormalizer;
        $nisnNormalizer = new NisnNormalizer;
        $fieldDefinitions = $proyek->fieldDefinitions;
        $count = 0;

        DB::transaction(function () use ($rows, $proyek, $nisNormalizer, $nisnNormalizer, $fieldDefinitions, &$count) {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                if ($this->blankToNull($row['nama'] ?? null) === null) {
                    continue;
                }

                $nis = $this->cleanValue($nisNormalizer->normalize($row['nis'] ?? null));
                $nisn = $this->cleanValue($nisnNormalizer->normalize($row['nisn'] ?? null));

                [$coreAttributes, $dynamicData] = $this->buildAttributesFromRow($row, $fieldDefinitions);
                $coreAttributes['nis'] = $nis;
                $coreAttributes['nisn'] = $nisn;

                $existing = $this->findExisting($proyek, $nis, $nisn);

                if ($existing !== null && $existing->is_locked) {
                    continue;
                }

                if ($existing !== null) {
                    // Siswa yang sudah ada TIDAK ditimpa penuh — cuma field yang masih
                    // kosong yang diisi. Upload roster ulang seharusnya menambah siswa
                    // baru & menutup yang masih bolong, bukan menghapus data yang sudah
                    // digabung dari Google Form (bisa sudah lebih akurat dari form
                    // daripada hasil parsing roster yang baru).
                    $updates = $this->onlyBlankFields($existing, $coreAttributes);
                    $newDynamic = $this->onlyBlankDynamicFields($existing, $dynamicData);

                    if ($newDynamic !== []) {
                        $updates['data'] = array_merge($existing->data ?? [], $newDynamic);
                    }

                    if ($updates !== []) {
                        $existing->update($updates);
                    }
                } else {
                    VerifikasiSiswa::create(array_merge($coreAttributes, [
                        'proyek_id' => $proyek->id,
                        'status' => 'belum_isi',
                        'data' => $dynamicData,
                    ]));
                }

                $count++;
            }
        });

        session()->forget("verifikasi_siswa.roster_review.{$proyek->id}");

        $logger->log($proyek, 'roster_saved', "{$count} baris roster disimpan/diperbarui");

        return redirect()->route('internal.verifikasi-siswa.show', $proyek)->with('status', "{$count} baris roster berhasil disimpan.");
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<int, VerifikasiSiswaFieldDefinition>  $fieldDefinitions
     * @return array{0: array<string, ?string>, 1: array<string, ?string>} [core, dinamis]
     */
    private function buildAttributesFromRow(array $row, Collection $fieldDefinitions): array
    {
        $core = [];
        $dynamic = [];

        foreach ($fieldDefinitions as $field) {
            if (in_array($field->key, ['nis', 'nisn'], true)) {
                continue;
            }

            $raw = $row[$field->key] ?? null;
            $value = $field->tipe === 'date' ? $this->parseTanggalLahir($raw) : $this->blankToNull($raw);

            if ($field->is_core) {
                $core[$field->key] = $value;
            } else {
                $dynamic[$field->key] = $value;
            }
        }

        return [$core, $dynamic];
    }

    /**
     * @param  array<string, ?string>  $attributes
     * @return array<string, string>
     */
    private function onlyBlankFields(VerifikasiSiswa $existing, array $attributes): array
    {
        return array_filter(
            $attributes,
            fn ($value, $key) => $value !== null && $existing->{$key} === null,
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @param  array<string, ?string>  $dynamicData
     * @return array<string, string>
     */
    private function onlyBlankDynamicFields(VerifikasiSiswa $existing, array $dynamicData): array
    {
        $current = $existing->data ?? [];

        return array_filter(
            $dynamicData,
            fn ($value, $key) => $value !== null && ($current[$key] ?? null) === null,
            ARRAY_FILTER_USE_BOTH,
        );
    }

    private function findExisting(VerifikasiSiswaProyek $proyek, ?string $nis, ?string $nisn): ?VerifikasiSiswa
    {
        if ($nisn !== null) {
            $existing = VerifikasiSiswa::where('proyek_id', $proyek->id)->where('nisn', $nisn)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        if ($nis !== null) {
            return VerifikasiSiswa::where('proyek_id', $proyek->id)->where('nis', $nis)->first();
        }

        return null;
    }

    private function cleanValue(FieldResult $fieldResult): ?string
    {
        return $fieldResult->status === 'invalid' ? null : $fieldResult->value;
    }

    private function blankToNull(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseTanggalLahir(?string $value): ?string
    {
        $value = $this->blankToNull($value);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
}
