@extends('layouts.internal')

@section('title', "{$proyek->nama} - Internal")

@section('content')
@include('internal.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-neutral-900">{{ $proyek->nama }}</h1>
            <p class="text-xs text-neutral-400 mt-1">
                {{ $siswa->count() }} siswa di roster
                @if ($proyek->google_sheet_id)
                    &middot; Sinkron terakhir: baris ke-{{ $proyek->last_synced_row }}
                @else
                    &middot; Google Sheet belum diatur
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if ($proyek->google_sheet_id)
                <form method="POST" action="{{ route('internal.verifikasi-siswa.sinkron', $proyek) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                        Sinkron Sekarang
                    </button>
                </form>
            @endif
            <a href="{{ route('internal.verifikasi-siswa.field.index', $proyek) }}" class="rounded-lg border border-neutral-300 px-3 py-1.5 text-xs font-semibold text-neutral-700 hover:bg-neutral-50">
                Atur Field
            </a>
            <a href="{{ route('internal.verifikasi-siswa.index') }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Daftar Proyek</a>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-sm font-bold text-neutral-900">Upload Roster</h2>
        <p class="mt-1 text-xs text-neutral-500">
            Roster resmi sekolah (PDF atau Excel). Hasil baca akan ditinjau &amp; bisa dikoreksi dulu sebelum disimpan permanen.
        </p>

        <form method="POST" action="{{ route('internal.verifikasi-siswa.roster.upload', $proyek) }}" enctype="multipart/form-data" class="mt-3 flex items-center gap-3">
            @csrf
            <input type="file" name="file" accept=".pdf,.xlsx,.xls" required
                   class="text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:text-rose-600 file:font-semibold hover:file:bg-rose-100">
            <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 shrink-0">
                Upload &amp; Tinjau
            </button>
        </form>
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Ringkasan per Kelas</h2>
        <div class="mt-3 overflow-x-auto rounded-xl border border-neutral-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                        <th class="py-2 px-3">Kelas</th>
                        <th class="py-2 px-3">Total</th>
                        <th class="py-2 px-3">Belum Isi</th>
                        <th class="py-2 px-3">Terisi</th>
                        <th class="py-2 px-3">Konflik</th>
                        <th class="py-2 px-3">Terkunci</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ringkasanPerKelas as $kelas => $r)
                        <tr class="border-b border-neutral-100">
                            <td class="py-2 px-3 font-medium text-neutral-800">{{ $kelas ?? '(tanpa kelas)' }}</td>
                            <td class="py-2 px-3 text-neutral-600">{{ $r['total'] }}</td>
                            <td class="py-2 px-3">{{ $r['belum_isi'] }}</td>
                            <td class="py-2 px-3">{{ $r['terisi'] }}</td>
                            <td class="py-2 px-3">{{ $r['konflik'] }}</td>
                            <td class="py-2 px-3">{{ $r['terkunci'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 px-3 text-center text-neutral-400">Belum ada roster diupload.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Perlu Perhatian — Konflik ({{ $konflik->count() }})</h2>

        @if ($konflik->isEmpty())
            <p class="mt-3 text-sm text-neutral-400">Tidak ada respons yang berkonflik.</p>
        @else
            <p class="mt-1 text-xs text-neutral-400">
                Pilih aksi untuk sebanyak apa pun respons di bawah, lalu klik <strong>Proses Semua</strong> sekali di akhir.
            </p>

            <form method="POST" action="{{ route('internal.verifikasi-siswa.respons.bulk-resolve', $proyek) }}" class="mt-3">
                @csrf

                <div class="space-y-3">
                    @foreach ($konflik as $response)
                        <div class="rounded-xl border border-neutral-200 bg-white p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium bg-red-50 text-red-700 border-red-200">
                                    Konflik
                                </span>
                                <span class="text-xs text-neutral-400">Respons #{{ $response->sheet_row_number }}</span>
                            </div>

                            <div class="mt-2 text-sm">
                                <p class="font-medium text-neutral-800">{{ $response->nama ?? '—' }}</p>
                                <p class="text-neutral-500 text-xs mt-0.5">
                                    Kelas: {{ $response->kelas ?? '—' }} &middot;
                                    NIS: {{ $response->nis ?? '—' }} &middot;
                                    NISN: {{ $response->nisn ?? '—' }}
                                </p>
                            </div>

                            @if ($response->matchedSiswa)
                                <div class="mt-3 rounded-lg bg-neutral-50 border border-neutral-200 p-3">
                                    <p class="text-xs text-neutral-400 mb-1">Kandidat siswa di roster (butuh dikonfirmasi karena {{ $response->matchedSiswa->is_locked ? 'sudah dikunci' : 'nama/data tidak cukup mirip' }}):</p>
                                    <p class="text-sm font-medium text-neutral-800">{{ $response->matchedSiswa->nama ?? '—' }}</p>
                                    <p class="text-xs text-neutral-500 mt-0.5">
                                        Kelas: {{ $response->matchedSiswa->kelas ?? '—' }} &middot;
                                        NIS: {{ $response->matchedSiswa->nis ?? '—' }} &middot;
                                        NISN: {{ $response->matchedSiswa->nisn ?? '—' }}
                                    </p>
                                </div>

                                <select name="action[{{ $response->id }}]" class="mt-3 w-full rounded-lg border-neutral-300 text-xs focus:border-rose-500 focus:ring-rose-500">
                                    <option value="">-- Lewati respons ini --</option>
                                    <option value="confirm">Konfirmasi Cocok</option>
                                    <option value="reject">Tolak</option>
                                </select>
                            @else
                                @php $kandidatSekelas = $siswa->where('kelas', $response->kelas); @endphp
                                <select name="action[{{ $response->id }}]" class="mt-3 w-full rounded-lg border-neutral-300 text-xs focus:border-rose-500 focus:ring-rose-500">
                                    <option value="">-- Lewati respons ini --</option>
                                    @foreach ($kandidatSekelas as $s)
                                        <option value="link:{{ $s->id }}">Hubungkan ke {{ $s->nama }} ({{ $s->kelas }}, NISN {{ $s->nisn ?? '?' }})</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="mt-4 w-full rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                    Proses Semua
                </button>
            </form>
        @endif
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Ada Perbedaan Data ({{ $adaDiskrepansi->count() }})</h2>
        <p class="mt-1 text-xs text-neutral-400">Sudah tercocok, tapi beberapa field beda antara roster &amp; respons form. Pilih data mana yang benar untuk tiap field, lalu klik <strong>Proses Semua</strong> sekali di akhir.</p>

        @if ($adaDiskrepansi->isEmpty())
            <p class="mt-3 text-sm text-neutral-400">Tidak ada perbedaan data.</p>
        @else
            @php $fieldKeyCore = ['nama', 'kelas', 'nis', 'nisn']; @endphp

            <form method="POST" action="{{ route('internal.verifikasi-siswa.perbedaan.bulk-resolve', $proyek) }}" class="mt-3">
                @csrf

                <div class="space-y-3">
                    @foreach ($adaDiskrepansi as $response)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="font-medium text-neutral-800">{{ $response->matchedSiswa->nama ?? $response->nama }}</p>

                            <div class="mt-2 space-y-2">
                                @foreach ($response->discrepancies ?? [] as $field)
                                    @php
                                        $isCore = in_array($field, $fieldKeyCore, true);
                                        $rosterValue = $isCore ? $response->matchedSiswa->{$field} : ($response->matchedSiswa->data[$field] ?? null);
                                        $formValue = $isCore ? $response->{$field} : ($response->data[$field] ?? null);
                                    @endphp
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <span class="w-28 shrink-0 font-semibold text-neutral-600">{{ $labelField[$field] ?? $field }}</span>
                                        <span class="text-neutral-500">Roster: <strong class="text-neutral-800">{{ $rosterValue ?? '—' }}</strong></span>
                                        <span class="text-neutral-500">Form: <strong class="text-neutral-800">{{ $formValue ?? '—' }}</strong></span>
                                        <select name="resolusi[{{ $response->id }}][{{ $field }}]" class="ml-auto rounded-lg border-neutral-300 text-xs focus:border-rose-500 focus:ring-rose-500">
                                            <option value="">-- Lewati --</option>
                                            <option value="form">Pakai Data Form</option>
                                            <option value="roster">Pakai Data Roster</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="mt-4 w-full rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                    Proses Semua
                </button>
            </form>
        @endif
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Daftar Siswa</h2>
        <p class="mt-1 text-xs text-neutral-400">Klik nama kelas untuk buka/tutup daftar siswanya.</p>

        @php $siswaPerKelas = $siswa->groupBy('kelas'); @endphp

        <div class="mt-3 space-y-2">
            @forelse ($siswaPerKelas as $kelas => $anggotaKelas)
                <details class="rounded-xl border border-neutral-200 bg-white overflow-hidden">
                    <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-neutral-800 hover:bg-neutral-50 flex flex-wrap items-center gap-2">
                        <span>{{ $kelas ?? '(tanpa kelas)' }}</span>
                        <span class="text-xs font-normal text-neutral-400">{{ $anggotaKelas->count() }} siswa</span>
                        @php $ringkasan = $ringkasanPerKelas[$kelas] ?? null; @endphp
                        @if ($ringkasan && $ringkasan['belum_isi'] > 0)
                            <span class="inline-block rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                {{ $ringkasan['belum_isi'] }} belum isi
                            </span>
                        @endif
                    </summary>

                    <div class="border-t border-neutral-200 px-4 py-2 bg-neutral-50 flex justify-end">
                        @if ($kelas !== null)
                            <a href="{{ route('internal.verifikasi-siswa.ekspor', [$proyek, $kelas]) }}" class="text-xs font-semibold text-rose-600 hover:underline">
                                Ekspor CSV Kelas {{ $kelas }} &darr;
                            </a>
                        @endif
                    </div>

                    <div class="overflow-x-auto border-t border-neutral-200">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                                    <th class="py-2 px-3">Nama</th>
                                    <th class="py-2 px-3">NIS</th>
                                    <th class="py-2 px-3">NISN</th>
                                    <th class="py-2 px-3">Status</th>
                                    <th class="py-2 px-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($anggotaKelas as $s)
                                    <tr class="border-b border-neutral-100">
                                        <td class="py-2 px-3 font-medium text-neutral-800">{{ $s->nama }}</td>
                                        <td class="py-2 px-3 text-neutral-500 text-xs">{{ $s->nis ?? '—' }}</td>
                                        <td class="py-2 px-3 text-neutral-500 text-xs">{{ $s->nisn ?? '—' }}</td>
                                        <td class="py-2 px-3">
                                            @if ($s->is_locked)
                                                <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium bg-neutral-100 text-neutral-500 border-neutral-200">Terkunci</span>
                                            @elseif ($s->status === 'terisi')
                                                <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 border-emerald-200">Terisi</span>
                                            @else
                                                <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium bg-amber-50 text-amber-700 border-amber-200">Belum Isi</span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3">
                                            @if ($s->is_locked)
                                                <form method="POST" action="{{ route('internal.verifikasi-siswa.siswa.unlock', [$proyek, $s]) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-medium text-rose-600 hover:underline">Buka Kunci</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('internal.verifikasi-siswa.siswa.lock', [$proyek, $s]) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-medium text-neutral-500 hover:underline">Kunci</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @empty
                <p class="text-sm text-neutral-400">Belum ada siswa. Upload roster dulu.</p>
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Log Aktivitas</h2>
        <div class="mt-3 space-y-2">
            @forelse ($activityLogs as $log)
                <div class="flex items-start gap-3 text-sm">
                    <span class="text-xs text-neutral-400 shrink-0 w-32">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                    <div>
                        <p class="text-neutral-700">{{ $log->description }}</p>
                        <p class="text-xs text-neutral-400">{{ $log->user->name ?? 'Sistem' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-neutral-400">Belum ada aktivitas tercatat.</p>
            @endforelse
        </div>
    </section>
</main>
@endsection
