<?php

namespace App\Services\Ongkir;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien untuk RajaOngkir API v2 (by Komerce) - dipakai untuk cari tujuan
 * pengiriman (autocomplete kecamatan) dan hitung ongkir domestik real-time.
 * https://rajaongkir.komerce.id
 */
class RajaOngkirService
{
    /**
     * @return array<int, array{id: int, label: string}>
     */
    public function cariTujuan(string $keyword): array
    {
        if (mb_strlen(trim($keyword)) < 3) {
            return [];
        }

        $response = $this->request()
            ->get('/destination/domestic-destination', [
                'search' => $keyword,
                'limit' => 10,
                'offset' => 0,
            ]);

        if (! $response->successful()) {
            Log::warning('RajaOngkir cariTujuan gagal: '.$response->status().' '.$response->body());

            return [];
        }

        return collect($response->json('data', []))
            ->map(fn ($row) => [
                'id' => $row['id'],
                'label' => $row['label'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{kode: string, layanan: string, deskripsi: string, nama: string, biaya: int, etd: string}>
     */
    public function hitungOngkir(int $tujuanId, int $beratGram): array
    {
        $origin = config('services.rajaongkir.origin_id');

        if (! $origin) {
            Log::warning('RajaOngkir: origin_id belum dikonfigurasi.');

            return [];
        }

        $response = $this->request()
            ->asForm()
            ->post('/calculate/domestic-cost', [
                'origin' => $origin,
                'destination' => $tujuanId,
                'weight' => max(1, $beratGram),
                'courier' => config('services.rajaongkir.courier'),
            ]);

        if (! $response->successful()) {
            Log::warning('RajaOngkir hitungOngkir gagal: '.$response->status().' '.$response->body());

            return [];
        }

        return collect($response->json('data', []))
            ->map(fn ($row) => [
                'kode' => $row['code'],
                'layanan' => $row['service'],
                'deskripsi' => $row['description'] ?? '',
                'nama' => $row['name'],
                'biaya' => (int) $row['cost'],
                'etd' => $row['etd'] ?? '',
            ])
            ->sortBy('biaya')
            ->values()
            ->all();
    }

    private function request()
    {
        return Http::withHeaders(['key' => config('services.rajaongkir.api_key')])
            ->baseUrl(config('services.rajaongkir.base_url'))
            ->timeout(10);
    }
}
