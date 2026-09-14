@extends('layouts.internal')

@section('title', 'Verifikasi Siswa - Internal')

@section('content')
@include('internal.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-xl font-bold text-neutral-900">Verifikasi Siswa</h1>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="mt-6 rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-sm font-bold text-neutral-900">Buat Proyek Baru</h2>
        <p class="mt-1 text-xs text-neutral-500">1 proyek = 1 sekolah / 1 sesi pemotretan.</p>

        <form method="POST" action="{{ route('internal.verifikasi-siswa.store') }}" class="mt-3 grid gap-3 sm:grid-cols-3">
            @csrf
            <input type="text" name="nama" placeholder="Nama proyek (mis. SMPN 15 Yogyakarta 2026)" required
                   class="sm:col-span-3 rounded-lg border border-neutral-300 px-3 py-2 text-sm">
            <input type="text" name="google_sheet_id" placeholder="Google Sheet ID (opsional, bisa diisi belakangan)"
                   class="sm:col-span-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                Buat Proyek
            </button>
        </form>
    </section>

    <div class="mt-6 overflow-x-auto rounded-xl border border-neutral-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                    <th class="py-2 px-3">Proyek</th>
                    <th class="py-2 px-3">Total Siswa</th>
                    <th class="py-2 px-3">Belum Isi</th>
                    <th class="py-2 px-3">Konflik</th>
                    <th class="py-2 px-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($proyeks as $proyek)
                    <tr class="border-b border-neutral-100 align-top">
                        <td class="py-2 px-3 font-medium text-neutral-800">{{ $proyek->nama }}</td>
                        <td class="py-2 px-3 text-neutral-600">{{ $proyek->siswa_count }}</td>
                        <td class="py-2 px-3">
                            @if ($proyek->belum_isi_count > 0)
                                <span class="inline-block rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                    {{ $proyek->belum_isi_count }}
                                </span>
                            @else
                                <span class="text-neutral-300 text-xs">0</span>
                            @endif
                        </td>
                        <td class="py-2 px-3">
                            @if ($proyek->konflik_count > 0)
                                <span class="inline-block rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">
                                    {{ $proyek->konflik_count }}
                                </span>
                            @else
                                <span class="text-neutral-300 text-xs">0</span>
                            @endif
                        </td>
                        <td class="py-2 px-3">
                            <a href="{{ route('internal.verifikasi-siswa.show', $proyek) }}" class="text-rose-600 font-medium hover:underline">Buka &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 px-3 text-center text-neutral-400">Belum ada proyek. Buat lewat form di atas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $proyeks->links() }}
    </div>
</main>
@endsection
