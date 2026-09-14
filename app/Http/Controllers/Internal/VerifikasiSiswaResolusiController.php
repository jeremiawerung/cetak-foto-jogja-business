<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\VerifikasiSiswa;
use App\Models\VerifikasiSiswaFormResponse;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\ActivityLogger;
use App\Services\VerifikasiSiswa\SiswaResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class VerifikasiSiswaResolusiController extends Controller
{
    public function confirm(VerifikasiSiswaProyek $proyek, VerifikasiSiswaFormResponse $response, SiswaResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $this->assertResponseBelongsToProyek($proyek, $response);

        try {
            $resolver->confirm($response);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['resolusi' => $e->getMessage()]);
        }

        $logger->log($proyek, 'respons_confirmed', "Konfirmasi pencocokan respons #{$response->sheet_row_number} ({$response->nama})");

        return back()->with('status', 'Pencocokan dikonfirmasi.');
    }

    public function reject(VerifikasiSiswaProyek $proyek, VerifikasiSiswaFormResponse $response, SiswaResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $this->assertResponseBelongsToProyek($proyek, $response);

        $resolver->reject($response);

        $logger->log($proyek, 'respons_rejected', "Tolak saran pencocokan respons #{$response->sheet_row_number} ({$response->nama})");

        return back()->with('status', 'Saran pencocokan ditolak.');
    }

    public function link(Request $request, VerifikasiSiswaProyek $proyek, VerifikasiSiswaFormResponse $response, SiswaResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $this->assertResponseBelongsToProyek($proyek, $response);

        $validated = $request->validate([
            'siswa_id' => ['required', 'integer'],
        ]);

        $siswa = VerifikasiSiswa::where('proyek_id', $proyek->id)->find($validated['siswa_id']);

        if ($siswa === null) {
            return back()->withErrors(['resolusi' => 'Siswa tujuan tidak ditemukan di proyek ini.']);
        }

        try {
            $resolver->linkManually($response, $siswa);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['resolusi' => $e->getMessage()]);
        }

        $logger->log($proyek, 'respons_linked', "Hubungkan manual respons #{$response->sheet_row_number} ({$response->nama}) <-> siswa {$siswa->nama}");

        return back()->with('status', 'Respons berhasil dihubungkan manual.');
    }

    /**
     * Proses SEMUA respons konflik dalam 1 submit — 1 dropdown aksi per respons, admin isi
     * sebanyak yang mau lalu klik 1 tombol di akhir (pola sama seperti bulkResolve() di
     * KartuPelajarBatchController lama — supaya submit 1 baris tidak mereset pilihan baris lain).
     */
    public function bulkResolve(Request $request, VerifikasiSiswaProyek $proyek, SiswaResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['nullable', 'array'],
            'action.*' => ['nullable', 'string'],
        ]);

        $actions = array_filter($validated['action'] ?? [], fn ($v) => $v !== null && $v !== '');

        $counts = ['confirm' => 0, 'reject' => 0, 'link' => 0];
        $errors = [];

        foreach ($actions as $responseId => $action) {
            $response = VerifikasiSiswaFormResponse::where('proyek_id', $proyek->id)->find((int) $responseId);

            if ($response === null) {
                continue;
            }

            try {
                if ($action === 'confirm') {
                    $resolver->confirm($response);
                    $counts['confirm']++;
                } elseif ($action === 'reject') {
                    $resolver->reject($response);
                    $counts['reject']++;
                } elseif (str_starts_with($action, 'link:')) {
                    $siswa = VerifikasiSiswa::where('proyek_id', $proyek->id)->find((int) substr($action, 5));

                    if ($siswa === null) {
                        $errors[] = "Respons #{$response->sheet_row_number} ({$response->nama}): siswa tujuan tidak ditemukan.";

                        continue;
                    }

                    $resolver->linkManually($response, $siswa);
                    $counts['link']++;
                }
            } catch (InvalidArgumentException $e) {
                $errors[] = "Respons #{$response->sheet_row_number} ({$response->nama}): {$e->getMessage()}";
            }
        }

        $total = array_sum($counts);

        if ($total > 0) {
            $logger->log(
                $proyek,
                'respons_bulk_resolve',
                "Proses massal: {$counts['confirm']} dikonfirmasi, {$counts['reject']} ditolak, {$counts['link']} dihubungkan manual",
                $counts,
            );
        }

        if ($errors !== []) {
            return back()->withErrors(['resolusi' => implode(' | ', $errors)]);
        }

        if ($total === 0) {
            return back()->withErrors(['resolusi' => 'Tidak ada aksi yang dipilih.']);
        }

        return back()->with('status', "Berhasil memproses {$total} respons ({$counts['confirm']} dikonfirmasi, {$counts['reject']} ditolak, {$counts['link']} dihubungkan).");
    }

    /**
     * Proses SEMUA field "Ada Perbedaan Data" dalam 1 submit — 1 dropdown per pasangan
     * (respons, field): "Pakai Form" (timpa nilai siswa) atau "Pakai Roster" (pertahankan,
     * cuma coret dari daftar). Pola sama seperti bulkResolve() — 1 tombol submit di akhir.
     */
    public function bulkResolveDiscrepancy(Request $request, VerifikasiSiswaProyek $proyek, SiswaResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'resolusi' => ['nullable', 'array'],
            'resolusi.*' => ['nullable', 'array'],
            'resolusi.*.*' => ['nullable', 'string', 'in:form,roster'],
        ]);

        $counts = ['form' => 0, 'roster' => 0];
        $errors = [];

        foreach ($validated['resolusi'] ?? [] as $responseId => $fields) {
            $response = VerifikasiSiswaFormResponse::where('proyek_id', $proyek->id)->find((int) $responseId);

            if ($response === null) {
                continue;
            }

            foreach ($fields as $field => $action) {
                if ($action === null || $action === '') {
                    continue;
                }

                try {
                    $resolver->resolveDiscrepancy($response, $field, $action === 'form');
                    $counts[$action]++;
                } catch (InvalidArgumentException $e) {
                    $errors[] = "Respons #{$response->sheet_row_number} ({$response->nama}), field {$field}: {$e->getMessage()}";
                }
            }
        }

        $total = array_sum($counts);

        if ($total > 0) {
            $logger->log(
                $proyek,
                'perbedaan_bulk_resolve',
                "Proses massal perbedaan data: {$counts['form']} pakai data form, {$counts['roster']} pertahankan data roster",
                $counts,
            );
        }

        if ($errors !== []) {
            return back()->withErrors(['resolusi' => implode(' | ', $errors)]);
        }

        if ($total === 0) {
            return back()->withErrors(['resolusi' => 'Tidak ada aksi yang dipilih.']);
        }

        return back()->with('status', "Berhasil memproses {$total} perbedaan data ({$counts['form']} pakai form, {$counts['roster']} pertahankan roster).");
    }

    public function lock(VerifikasiSiswaProyek $proyek, VerifikasiSiswa $siswa, SiswaResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $this->assertSiswaBelongsToProyek($proyek, $siswa);

        $resolver->lock($siswa);

        $logger->log($proyek, 'siswa_locked', "Kunci data siswa {$siswa->nama}");

        return back()->with('status', 'Siswa dikunci — sinkron berikutnya tidak akan menimpa datanya.');
    }

    public function unlock(VerifikasiSiswaProyek $proyek, VerifikasiSiswa $siswa, SiswaResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $this->assertSiswaBelongsToProyek($proyek, $siswa);

        $resolver->unlock($siswa);

        $logger->log($proyek, 'siswa_unlocked', "Buka kunci data siswa {$siswa->nama}");

        return back()->with('status', 'Kunci siswa dibuka.');
    }

    private function assertResponseBelongsToProyek(VerifikasiSiswaProyek $proyek, VerifikasiSiswaFormResponse $response): void
    {
        abort_unless($response->proyek_id === $proyek->id, 404);
    }

    private function assertSiswaBelongsToProyek(VerifikasiSiswaProyek $proyek, VerifikasiSiswa $siswa): void
    {
        abort_unless($siswa->proyek_id === $proyek->id, 404);
    }
}
