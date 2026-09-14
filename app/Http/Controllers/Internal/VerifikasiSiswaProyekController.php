<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\ActivityLogger;
use App\Services\VerifikasiSiswa\GFormSyncer;
use App\Services\VerifikasiSiswa\ProyekFieldSeeder;
use App\Services\VerifikasiSiswa\SiswaExportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VerifikasiSiswaProyekController extends Controller
{
    public function index(): View
    {
        $proyeks = VerifikasiSiswaProyek::withCount([
            'siswa',
            'siswa as belum_isi_count' => fn ($q) => $q->where('status', 'belum_isi'),
            'formResponses as konflik_count' => fn ($q) => $q->where('status', 'conflict'),
        ])->latest('id')->paginate(20);

        return view('internal.verifikasi-siswa.proyek.index', compact('proyeks'));
    }

    public function store(Request $request, ProyekFieldSeeder $seeder): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'google_sheet_id' => ['nullable', 'string', 'max:255'],
            'google_sheet_range' => ['nullable', 'string', 'max:255'],
        ]);

        $proyek = VerifikasiSiswaProyek::create($validated);
        $seeder->seedDefaults($proyek);

        return redirect()->route('internal.verifikasi-siswa.show', $proyek)->with('status', 'Proyek berhasil dibuat.');
    }

    public function show(VerifikasiSiswaProyek $proyek): View
    {
        $siswa = $proyek->siswa()->orderBy('kelas')->orderBy('nama')->get();
        $responsKonflikPerKelas = $proyek->formResponses()->where('status', 'conflict')->get()->groupBy('kelas');

        $ringkasanPerKelas = $siswa->groupBy('kelas')->map(fn ($group, $kelas) => [
            'total' => $group->count(),
            'belum_isi' => $group->where('status', 'belum_isi')->count(),
            'terisi' => $group->where('status', 'terisi')->count(),
            'konflik' => $responsKonflikPerKelas->get($kelas, collect())->count(),
            'terkunci' => $group->where('is_locked', true)->count(),
        ]);

        $konflik = $proyek->formResponses()
            ->where('status', 'conflict')
            ->with('matchedSiswa')
            ->orderBy('sheet_row_number')
            ->get();

        $adaDiskrepansi = $proyek->formResponses()
            ->where('status', 'matched')
            ->whereNotNull('discrepancies')
            ->with('matchedSiswa')
            ->orderBy('sheet_row_number')
            ->get()
            ->filter(fn ($r) => ($r->discrepancies ?? []) !== []);

        return view('internal.verifikasi-siswa.proyek.show', [
            'proyek' => $proyek,
            'siswa' => $siswa,
            'ringkasanPerKelas' => $ringkasanPerKelas,
            'konflik' => $konflik,
            'adaDiskrepansi' => $adaDiskrepansi,
            'labelField' => $proyek->fieldDefinitions->pluck('label', 'key')->all(),
            'activityLogs' => $proyek->activityLogs()->with('user')->limit(30)->get(),
        ]);
    }

    public function syncNow(VerifikasiSiswaProyek $proyek, GFormSyncer $syncer, ActivityLogger $logger): RedirectResponse
    {
        if ($proyek->google_sheet_id === null) {
            return back()->withErrors(['sinkron' => 'Proyek ini belum diatur Google Sheet ID-nya.']);
        }

        $result = $syncer->sync($proyek);

        $logger->log($proyek, 'sync', $result['jumlah_baru'].' respons baru disinkron dari Google Form', [
            'jumlah_baru' => $result['jumlah_baru'],
            'status_setelah_cocok' => $result['status'],
        ]);

        return back()->with('status', 'Sinkron selesai: '.$result['jumlah_baru'].' respons baru diproses.');
    }

    public function exportKelas(VerifikasiSiswaProyek $proyek, string $kelas, SiswaExportBuilder $exportBuilder, ActivityLogger $logger): Response
    {
        $csv = $exportBuilder->toCsv($proyek, $kelas);

        $logger->log($proyek, 'export_downloaded', "CSV ekspor kelas {$kelas} diunduh");

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="kartu-pelajar-'.Str::slug($proyek->nama).'-'.Str::slug($kelas).'-'.now()->format('Ymd-His').'.csv"',
        ]);
    }
}
