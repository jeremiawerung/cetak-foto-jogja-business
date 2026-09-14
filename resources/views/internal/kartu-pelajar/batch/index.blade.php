@extends('layouts.internal')

@section('title', 'Batch Kartu Pelajar - Internal')

@section('content')
@include('internal.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-neutral-900">Daftar Batch</h1>
        <a href="{{ route('internal.kartu-pelajar.index') }}" class="text-sm font-medium text-rose-600 hover:underline">+ Upload &amp; Audit Baru</a>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-neutral-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                    <th class="py-2 px-3">Batch</th>
                    <th class="py-2 px-3">Dibuat</th>
                    <th class="py-2 px-3">Sumber</th>
                    <th class="py-2 px-3">Total Baris</th>
                    <th class="py-2 px-3">Perlu Perhatian</th>
                    <th class="py-2 px-3">Belum Diproses</th>
                    <th class="py-2 px-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batches as $batch)
                    <tr class="border-b border-neutral-100 align-top">
                        <td class="py-2 px-3 font-medium text-neutral-800">#{{ $batch->id }}</td>
                        <td class="py-2 px-3 text-neutral-600">{{ $batch->created_at->translatedFormat('d M Y, H:i') }}</td>
                        <td class="py-2 px-3 text-neutral-600 text-xs">
                            {{ $batch->sumber_file_excel ?? '—' }}<br>
                            {{ $batch->sumber_file_gform ?? '—' }}
                        </td>
                        <td class="py-2 px-3 text-neutral-600">{{ $batch->rows_count }}</td>
                        <td class="py-2 px-3">
                            @if ($batch->perlu_perhatian_count > 0)
                                <span class="inline-block rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">
                                    {{ $batch->perlu_perhatian_count }}
                                </span>
                            @else
                                <span class="text-neutral-300 text-xs">0</span>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-neutral-600">{{ $batch->belum_diproses_count }}</td>
                        <td class="py-2 px-3">
                            <a href="{{ route('internal.kartu-pelajar.batch.show', $batch) }}" class="text-rose-600 font-medium hover:underline">Buka &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 px-3 text-center text-neutral-400">Belum ada batch. Upload data lewat "Upload & Audit Baru".</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $batches->links() }}
    </div>
</main>
@endsection
