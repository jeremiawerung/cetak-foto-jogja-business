<?php

namespace App\Http\Controllers;

use App\Models\PhotographerBooking;
use App\Services\AirtableLogger;
use App\Services\WhatsAppLinkBuilder;
use Illuminate\Http\Request;

class SewaFotograferController extends Controller
{
    public function index()
    {
        $jamSlot = config('booking.jam_slot');

        return view('sewa-fotografer.index', compact('jamSlot'));
    }

    public function ketersediaan(Request $request)
    {
        $validated = $request->validate([
            'bulan' => ['required', 'date_format:Y-m'],
        ]);

        [$tahun, $bulan] = explode('-', $validated['bulan']);

        $bookings = PhotographerBooking::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->get(['tanggal', 'jam']);

        $terpakai = [];
        foreach ($bookings as $booking) {
            $tanggal = $booking->tanggal->format('Y-m-d');
            $jam = substr($booking->jam, 0, 5);
            $terpakai[$tanggal][] = $jam;
        }

        return response()->json($terpakai);
    }

    public function store(Request $request, AirtableLogger $airtable, WhatsAppLinkBuilder $whatsapp)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:30'],
            'lokasi' => ['required', 'string', 'max:255'],
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'jam' => ['required', 'string', 'in:'.implode(',', config('booking.jam_slot'))],
            'estimasi_orang' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $bentrok = PhotographerBooking::whereDate('tanggal', $validated['tanggal'])
            ->where('jam', $validated['jam'])
            ->exists();

        if ($bentrok) {
            return response()->json([
                'message' => 'Mohon maaf, tanggal dan jam tersebut sudah dibooking. Silakan pilih jadwal lain.',
            ], 422);
        }

        try {
            $booking = PhotographerBooking::create($validated);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Mohon maaf, tanggal dan jam tersebut baru saja dibooking orang lain. Silakan pilih jadwal lain.',
            ], 422);
        }

        $airtable->log(config('services.airtable.table_sewa_fotografer'), [
            'Nama' => $validated['nama'],
            'No HP' => $validated['no_hp'],
            'Lokasi' => $validated['lokasi'],
            'Tanggal' => $validated['tanggal'],
            'Jam' => $validated['jam'],
            'Estimasi Orang' => $validated['estimasi_orang'] ?? '',
            'Catatan' => $validated['catatan'] ?? '',
            'Waktu Booking' => now()->toDateTimeString(),
        ]);

        $pesan = $this->susunPesanWhatsApp($validated);
        $waLink = $whatsapp->build($pesan);

        return response()->json([
            'booking_id' => $booking->id,
            'wa_link' => $waLink,
        ]);
    }

    private function susunPesanWhatsApp(array $data): string
    {
        $baris = [];
        $baris[] = 'Halo Cetak Foto Jogja, saya ingin booking sewa fotografer:';
        $baris[] = '';
        $baris[] = 'Lokasi: '.$data['lokasi'];
        $baris[] = 'Tanggal: '.$data['tanggal'];
        $baris[] = 'Jam: '.$data['jam'];

        if (! empty($data['estimasi_orang'])) {
            $baris[] = 'Estimasi Jumlah Orang: '.$data['estimasi_orang'];
        }

        if (! empty($data['catatan'])) {
            $baris[] = 'Catatan: '.$data['catatan'];
        }

        $baris[] = '';
        $baris[] = 'Nama: '.$data['nama'];
        $baris[] = 'No HP: '.$data['no_hp'];

        return implode("\n", $baris);
    }
}
