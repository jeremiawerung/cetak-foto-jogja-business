<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanPembayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PengaturanPembayaranController extends Controller
{
    private const QRIS_DIR = 'images/pembayaran';

    public function edit(): View
    {
        return view('admin.pengaturan-pembayaran.edit', [
            'pengaturan' => PengaturanPembayaran::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_bank' => ['required', 'string', 'max:255'],
            'no_rekening' => ['required', 'string', 'max:50'],
            'atas_nama' => ['required', 'string', 'max:255'],
            'qris_gambar' => ['nullable', 'image', 'max:4096'],
        ]);

        $pengaturan = PengaturanPembayaran::current();

        $pengaturan->nama_bank = $validated['nama_bank'];
        $pengaturan->no_rekening = $validated['no_rekening'];
        $pengaturan->atas_nama = $validated['atas_nama'];

        if ($request->hasFile('qris_gambar')) {
            $this->hapusQrisLama($pengaturan);
            $pengaturan->qris_gambar = $this->simpanQris($request);
        }

        $pengaturan->save();

        return back()->with('status', 'Pengaturan pembayaran berhasil disimpan.');
    }

    private function simpanQris(Request $request): string
    {
        $file = $request->file('qris_gambar');
        $nama = Str::random(20).'.'.$file->getClientOriginalExtension();

        $file->move(public_path(self::QRIS_DIR), $nama);

        return self::QRIS_DIR.'/'.$nama;
    }

    private function hapusQrisLama(PengaturanPembayaran $pengaturan): void
    {
        if ($pengaturan->qris_gambar && file_exists(public_path($pengaturan->qris_gambar))) {
            @unlink(public_path($pengaturan->qris_gambar));
        }
    }
}
