@extends('layouts.internal')

@section('title', "Tinjau Roster - {$proyek->nama}")

@section('content')
@include('internal.partials.header')

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-10 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-neutral-900">Tinjau Hasil Roster</h1>
            <p class="text-xs text-neutral-400 mt-1">{{ count($rows) }} baris terbaca dari file. Periksa &amp; koreksi sebelum disimpan permanen.</p>
        </div>
        <a href="{{ route('internal.verifikasi-siswa.show', $proyek) }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Batal, kembali</a>
    </div>

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
            {{ $errors->first() }}
        </div>
    @endif

    @if (!empty($warnings))
        <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700 space-y-1">
            @foreach ($warnings as $warning)
                <p>&#9888; {{ $warning }}</p>
            @endforeach
        </div>
    @endif

    {{--
        Baris roster bisa ratusan (1 sekolah penuh) - kalau tiap sel jadi <input> bernama
        rows[i][field] sendiri-sendiri, jumlah field POST bisa lewat batas max_input_vars
        PHP (default 1000) dan sebagian baris diam-diam hilang tanpa error. Makanya semua
        nilai dikumpulkan jadi 1 JSON di hidden input "data" saat submit, bukan dikirim
        sebagai array field terpisah.

        Kolom tabel di bawah dibangun dari field_definitions proyek ini (bisa beda-beda
        per sekolah, termasuk field custom) - bukan daftar kolom tetap lagi. NIS/NISN
        selalu ada karena itu kunci pencocokan, tidak pernah bisa dihapus dari proyek.
    --}}
    <form method="POST" action="{{ route('internal.verifikasi-siswa.roster.simpan', $proyek) }}" id="form-tinjau-roster">
        @csrf
        <input type="hidden" name="data" id="input-data-json">

        <div class="overflow-x-auto rounded-xl border border-neutral-200">
            <table class="w-full text-sm" id="tabel-tinjau-roster">
                <thead>
                    <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                        <th class="py-2 px-2">NIS</th>
                        <th class="py-2 px-2">NISN</th>
                        @foreach ($fieldDefinitions as $field)
                            <th class="py-2 px-2">{{ $field->label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        <tr class="border-b border-neutral-100" data-row="{{ $i }}">
                            <td class="p-1"><input type="text" data-field="nis" value="{{ $row['nis'] ?? '' }}" class="w-20 rounded border-neutral-300 text-xs"></td>
                            <td class="p-1"><input type="text" data-field="nisn" value="{{ $row['nisn'] ?? '' }}" class="w-24 rounded border-neutral-300 text-xs"></td>
                            @foreach ($fieldDefinitions as $field)
                                <td class="p-1"><input type="text" data-field="{{ $field->key }}" value="{{ $row[$field->key] ?? '' }}" class="w-32 rounded border-neutral-300 text-xs"></td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="submit" class="mt-4 w-full rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
            Simpan ke Roster ({{ count($rows) }} baris)
        </button>
    </form>
</main>

<script>
    document.getElementById('form-tinjau-roster').addEventListener('submit', function (event) {
        var rows = [];

        document.querySelectorAll('#tabel-tinjau-roster tbody tr').forEach(function (tr) {
            var row = {};

            tr.querySelectorAll('input[data-field]').forEach(function (input) {
                row[input.dataset.field] = input.value;
            });

            rows.push(row);
        });

        document.getElementById('input-data-json').value = JSON.stringify(rows);
    });
</script>
@endsection
