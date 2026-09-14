@extends('layouts.internal')

@section('title', 'QA Kartu Pelajar - Internal')

@section('content')
@include('internal.partials.header')

<main class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-neutral-900">QA Kartu Pelajar — Audit Data</h1>
        <a href="{{ route('internal.kartu-pelajar.batch.index') }}" class="text-sm font-medium text-rose-600 hover:underline">Daftar Batch &rarr;</a>
    </div>
    <p class="mt-1 text-sm text-neutral-500">
        Upload file Excel master siswa (sheet "Sheet3") dan file hasil Google Form (PDF atau Excel).
        Sistem akan mengaudit & menormalisasi tiap baris, lalu menandai statusnya: bersih,
        perlu diperiksa, atau perlu direkonstruksi manual.
    </p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('internal.kartu-pelajar.audit') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-neutral-700">File Excel Master (.xlsx, sheet "Sheet3")</label>
            <input type="file" name="file_excel" accept=".xlsx" required
                   class="mt-1 w-full text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:text-rose-600 file:font-semibold hover:file:bg-rose-100">
        </div>

        <div>
            <label class="block text-sm font-medium text-neutral-700">File Hasil Google Form (.pdf atau .xlsx)</label>
            <input type="file" name="file_gform" accept=".pdf,.xlsx" required
                   class="mt-1 w-full text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:text-rose-600 file:font-semibold hover:file:bg-rose-100">
            <p class="mt-1 text-xs text-neutral-400">
                Kalau ada akses langsung ke Google Sheet-nya, ekspor sebagai .xlsx — hasilnya jauh lebih akurat daripada PDF.
            </p>
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 transition">
            Jalankan Audit
        </button>
    </form>

    <p class="mt-4 text-xs text-neutral-400">
        File yang diupload hanya diproses sementara dan langsung dihapus setelah dibaca — tidak disimpan permanen.
    </p>
</main>
@endsection
