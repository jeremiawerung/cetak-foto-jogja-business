@extends('layouts.internal')

@section('title', "Atur Field - {$proyek->nama}")

@section('content')
@include('internal.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-neutral-900">Atur Field — {{ $proyek->nama }}</h1>
            <p class="text-xs text-neutral-400 mt-1">Sesuaikan field data siswa & kolom ekspor CSV khusus untuk sekolah ini — beda sekolah bisa beda kebutuhan kartu.</p>
        </div>
        <a href="{{ route('internal.verifikasi-siswa.show', $proyek) }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Kembali</a>
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
        <h2 class="text-sm font-bold text-neutral-900">Field Data Siswa</h2>
        <p class="mt-1 text-xs text-neutral-500">
            Field ini dipakai saat baca roster/Google Form, digabungkan, & ditampilkan di dashboard. "Nama" & "Kelas" (inti) tidak bisa dihapus karena dipakai untuk pencocokan.
        </p>

        <div class="mt-3 overflow-x-auto rounded-lg border border-neutral-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                        <th class="py-2 px-3">Label</th>
                        <th class="py-2 px-3">Key</th>
                        <th class="py-2 px-3">Tipe</th>
                        <th class="py-2 px-3">Sumber Utama</th>
                        <th class="py-2 px-3">Urutan</th>
                        <th class="py-2 px-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fieldDefinitions as $field)
                        <tr class="border-b border-neutral-100">
                            <td class="py-2 px-3 font-medium text-neutral-800">{{ $field->label }}</td>
                            <td class="py-2 px-3 text-neutral-500 text-xs">{{ $field->key }}</td>
                            <td class="py-2 px-3 text-neutral-500 text-xs">{{ $field->tipe }}</td>
                            <td class="py-2 px-3 text-neutral-500 text-xs">{{ $field->sumber_utama }}</td>
                            <td class="py-2 px-3 text-neutral-500 text-xs">{{ $field->urutan }}</td>
                            <td class="py-2 px-3">
                                @if ($field->is_core)
                                    <span class="text-xs text-neutral-300">inti</span>
                                @else
                                    <form method="POST" action="{{ route('internal.verifikasi-siswa.field.destroy', [$proyek, $field]) }}" onsubmit="return confirm('Hapus field {{ $field->label }}? Data yang sudah tersimpan di field ini tidak akan otomatis ikut terhapus.');">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-rose-600 hover:underline">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('internal.verifikasi-siswa.field.store', $proyek) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            <input type="text" name="label" placeholder="Label (mis. Nomor Sekolah)" required class="rounded-lg border-neutral-300 text-sm">
            <input type="text" name="key" placeholder="Key (mis. nomor_sekolah)" required pattern="[a-z_][a-z0-9_]*" class="rounded-lg border-neutral-300 text-sm">
            <select name="tipe" class="rounded-lg border-neutral-300 text-sm">
                <option value="text">Teks biasa</option>
                <option value="date">Tanggal</option>
            </select>
            <select name="sumber_utama" class="rounded-lg border-neutral-300 text-sm">
                <option value="roster">Roster jadi acuan utama</option>
                <option value="form">Google Form jadi acuan utama</option>
            </select>
            <input type="number" name="urutan" placeholder="Urutan (opsional)" class="rounded-lg border-neutral-300 text-sm">
            <input type="text" name="kata_kunci" placeholder="Kata kunci header, pisah koma (mis. nomor sekolah, no sekolah)" class="rounded-lg border-neutral-300 text-sm sm:col-span-2">
            <button type="submit" class="sm:col-span-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                Tambah Field
            </button>
        </form>
    </section>

    <section class="rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-sm font-bold text-neutral-900">Kolom Ekspor CSV</h2>
        <p class="mt-1 text-xs text-neutral-500">Kolom, urutan, & label yang muncul di CSV yang diunduh untuk sekolah ini.</p>

        <div class="mt-3 overflow-x-auto rounded-lg border border-neutral-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                        <th class="py-2 px-3">Label CSV</th>
                        <th class="py-2 px-3">Sumber</th>
                        <th class="py-2 px-3">Urutan</th>
                        <th class="py-2 px-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($exportColumns as $column)
                        <tr class="border-b border-neutral-100">
                            <td class="py-2 px-3 font-medium text-neutral-800">{{ $column->label }}</td>
                            <td class="py-2 px-3 text-neutral-500 text-xs">
                                {{ match($column->sumber_tipe) { 'ttl' => 'Tempat + Tanggal Lahir', 'id_file' => 'ID_FILE', default => $column->field_key } }}
                            </td>
                            <td class="py-2 px-3 text-neutral-500 text-xs">{{ $column->urutan }}</td>
                            <td class="py-2 px-3">
                                <form method="POST" action="{{ route('internal.verifikasi-siswa.ekspor-kolom.destroy', [$proyek, $column]) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-rose-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('internal.verifikasi-siswa.ekspor-kolom.store', $proyek) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            <input type="text" name="label" placeholder="Label kolom CSV (mis. NOMOR SEKOLAH)" required class="rounded-lg border-neutral-300 text-sm">
            <select name="sumber_tipe" class="rounded-lg border-neutral-300 text-sm" onchange="document.getElementById('field-key-wrapper').hidden = this.value !== 'field'">
                <option value="field">Field data siswa</option>
                <option value="ttl">Gabungan Tempat + Tanggal Lahir (TTL)</option>
                <option value="id_file">ID_FILE</option>
            </select>
            <div id="field-key-wrapper">
                <select name="field_key" class="w-full rounded-lg border-neutral-300 text-sm">
                    <option value="nama">nama</option>
                    <option value="kelas">kelas</option>
                    <option value="nis">nis</option>
                    <option value="nisn">nisn</option>
                    @foreach ($fieldDefinitions as $field)
                        <option value="{{ $field->key }}">{{ $field->key }}</option>
                    @endforeach
                </select>
            </div>
            <input type="number" name="urutan" placeholder="Urutan (opsional)" class="rounded-lg border-neutral-300 text-sm">
            <button type="submit" class="sm:col-span-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                Tambah Kolom Ekspor
            </button>
        </form>
    </section>
</main>
@endsection
