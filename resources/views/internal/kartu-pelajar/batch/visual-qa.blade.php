@extends('layouts.internal')

@section('title', "Visual QA - Batch #{$batch->id}")

@section('content')
@include('internal.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-neutral-900">Visual QA — Batch #{{ $batch->id }}</h1>
        <a href="{{ route('internal.kartu-pelajar.batch.show', $batch) }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Kembali</a>
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
        <h2 class="text-sm font-bold text-neutral-900">Upload File Kartu Hasil Photoshop</h2>
        <p class="mt-1 text-xs text-neutral-500">
            Nama file harus persis sesuai ID_FILE (misal <code class="bg-neutral-100 px-1 rounded">KP-000123.psd</code>) —
            itu nama file yang otomatis dihasilkan Photoshop kalau kolom ID_FILE dari CSV ekspor dipakai sebagai nama output.
            File <code class="bg-neutral-100 px-1 rounded">.psd</code> asli didukung — preview diambil dari thumbnail yang sudah tertanam di dalam file-nya.
        </p>

        <form method="POST" action="{{ route('internal.kartu-pelajar.batch.visual-qa.upload', $batch) }}" enctype="multipart/form-data" class="mt-3 flex items-center gap-3">
            @csrf
            <input type="file" name="files[]" multiple accept=".psd,.jpg,.jpeg,.png" required
                   class="text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:text-rose-600 file:font-semibold hover:file:bg-rose-100">
            <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 shrink-0">
                Upload
            </button>
        </form>
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Ditemukan ({{ $report['ditemukan']->count() }})</h2>
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
            @forelse ($report['ditemukan'] as $file)
                <div class="rounded-lg border border-neutral-200 overflow-hidden">
                    @if ($file->previewUrl())
                        <img src="{{ $file->previewUrl() }}" alt="{{ $file->id_file }}" class="w-full aspect-square object-cover">
                    @else
                        <div class="w-full aspect-square bg-neutral-100 flex items-center justify-center">
                            <span class="text-xs font-semibold text-neutral-400">.PSD</span>
                        </div>
                    @endif
                    <p class="text-xs text-center py-1 text-neutral-500">{{ $file->id_file }}</p>
                </div>
            @empty
                <p class="text-sm text-neutral-400 col-span-full">Belum ada file yang diupload.</p>
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Hilang ({{ count($report['hilang']) }})</h2>
        <p class="text-xs text-neutral-500 mt-1">ID_FILE yang seharusnya ada (sudah tercocok &amp; siap ekspor) tapi belum ada file gambarnya.</p>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse ($report['hilang'] as $idFile)
                <span class="inline-block rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-medium text-red-700">{{ $idFile }}</span>
            @empty
                <p class="text-sm text-neutral-400">Tidak ada yang hilang — semua sudah lengkap.</p>
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Tidak Dikenali ({{ $report['tidak_dikenali']->count() }})</h2>
        <p class="text-xs text-neutral-500 mt-1">File yang diupload tapi namanya tidak cocok ID_FILE manapun di batch ini — mungkin salah batch atau nama file salah ketik.</p>
        <div class="mt-3 space-y-1">
            @forelse ($report['tidak_dikenali'] as $file)
                <p class="text-sm text-neutral-600">{{ $file->original_filename }}</p>
            @empty
                <p class="text-sm text-neutral-400">Tidak ada file yang tidak dikenali.</p>
            @endforelse
        </div>
    </section>
</main>
@endsection
