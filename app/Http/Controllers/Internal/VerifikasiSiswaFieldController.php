<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\VerifikasiSiswaExportColumn;
use App\Models\VerifikasiSiswaFieldDefinition;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman "Atur Field" per proyek — di sinilah field data siswa (di luar Nama/Kelas/
 * NIS/NISN yang tetap) & kolom ekspor CSV bisa disesuaikan per sekolah: hapus field
 * yang tidak dipakai sekolah itu (mis. Agama), tambah field custom (mis. Nomor
 * Sekolah), atur urutan & label kolom ekspor.
 */
class VerifikasiSiswaFieldController extends Controller
{
    public function index(VerifikasiSiswaProyek $proyek): View
    {
        return view('internal.verifikasi-siswa.proyek.atur-field', [
            'proyek' => $proyek,
            'fieldDefinitions' => $proyek->fieldDefinitions,
            'exportColumns' => $proyek->exportColumns,
        ]);
    }

    public function storeField(Request $request, VerifikasiSiswaProyek $proyek, ActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:50', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'label' => ['required', 'string', 'max:100'],
            'tipe' => ['required', 'in:text,date'],
            'sumber_utama' => ['required', 'in:roster,form'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'kata_kunci' => ['nullable', 'string', 'max:255'],
        ], [
            'key.regex' => 'Key cuma boleh huruf kecil, angka, underscore, dan tidak diawali angka (mis. "nomor_sekolah").',
        ]);

        if ($proyek->fieldDefinitions()->where('key', $validated['key'])->exists()) {
            return back()->withErrors(['key' => 'Field dengan key ini sudah ada di proyek ini.']);
        }

        $proyek->fieldDefinitions()->create([
            'key' => $validated['key'],
            'label' => $validated['label'],
            'tipe' => $validated['tipe'],
            'is_core' => false,
            'sumber_utama' => $validated['sumber_utama'],
            'urutan' => $validated['urutan'] ?? (($proyek->fieldDefinitions()->max('urutan') ?? 0) + 1),
            'kata_kunci_header' => $this->parseKataKunci($validated['kata_kunci'] ?? null, $validated['label']),
        ]);

        $logger->log($proyek, 'field_ditambah', "Field baru ditambahkan: {$validated['label']} ({$validated['key']})");

        return back()->with('status', 'Field baru berhasil ditambahkan.');
    }

    public function destroyField(VerifikasiSiswaProyek $proyek, VerifikasiSiswaFieldDefinition $field, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($field->proyek_id === $proyek->id, 404);

        if ($field->is_core) {
            return back()->withErrors(['field' => 'Field inti (Nama/Kelas) tidak bisa dihapus.']);
        }

        $field->delete();

        $logger->log($proyek, 'field_dihapus', "Field dihapus: {$field->label} ({$field->key})");

        return back()->with('status', 'Field berhasil dihapus.');
    }

    public function storeExportColumn(Request $request, VerifikasiSiswaProyek $proyek, ActivityLogger $logger): RedirectResponse
    {
        $validFieldKeys = array_merge(['nama', 'kelas', 'nis', 'nisn'], $proyek->fieldDefinitions->pluck('key')->all());

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'sumber_tipe' => ['required', 'in:field,ttl,id_file'],
            'field_key' => ['nullable', 'string', 'in:'.implode(',', $validFieldKeys)],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validated['sumber_tipe'] === 'field' && ($validated['field_key'] ?? null) === null) {
            return back()->withErrors(['field_key' => 'Pilih field sumbernya.']);
        }

        $proyek->exportColumns()->create([
            'label' => $validated['label'],
            'sumber_tipe' => $validated['sumber_tipe'],
            'field_key' => $validated['sumber_tipe'] === 'field' ? $validated['field_key'] : null,
            'urutan' => $validated['urutan'] ?? (($proyek->exportColumns()->max('urutan') ?? 0) + 1),
        ]);

        $logger->log($proyek, 'kolom_ekspor_ditambah', "Kolom ekspor baru ditambahkan: {$validated['label']}");

        return back()->with('status', 'Kolom ekspor berhasil ditambahkan.');
    }

    public function destroyExportColumn(VerifikasiSiswaProyek $proyek, VerifikasiSiswaExportColumn $column, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($column->proyek_id === $proyek->id, 404);

        $column->delete();

        $logger->log($proyek, 'kolom_ekspor_dihapus', "Kolom ekspor dihapus: {$column->label}");

        return back()->with('status', 'Kolom ekspor berhasil dihapus.');
    }

    /**
     * Ubah input teks admin (frasa dipisah koma) jadi struktur aturan AND-group/OR-alternatif
     * yang dipakai FieldHeaderMatcher — tiap frasa jadi 1 aturan (semua katanya harus ada
     * di header), beberapa frasa berarti salah satu aturan itu cocok sudah cukup.
     *
     * @return list<list<string>>
     */
    private function parseKataKunci(?string $raw, string $fallbackLabel): array
    {
        $raw = trim((string) $raw) !== '' ? $raw : $fallbackLabel;

        $frasaList = array_filter(array_map('trim', explode(',', $raw)));

        $aturan = [];

        foreach ($frasaList as $frasa) {
            $kata = array_values(array_filter(preg_split('/\s+/u', mb_strtolower($frasa)) ?: []));

            if ($kata !== []) {
                $aturan[] = $kata;
            }
        }

        return $aturan !== [] ? $aturan : [[mb_strtolower($fallbackLabel)]];
    }
}
