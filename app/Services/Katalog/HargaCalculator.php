<?php

namespace App\Services\Katalog;

class HargaCalculator
{
    /**
     * @return array{varian_nama: string|null, harga_satuan: int, satuan_label: string, total: int, custom: bool}
     */
    public function hitung(array $kategoriData, ?string $varianId, int $jumlah): array
    {
        if ($kategoriData['pricing_mode'] === 'tiered') {
            $hargaSatuan = 0;
            foreach ($kategoriData['tiers'] as $tier) {
                if ($jumlah >= $tier['min'] && ($tier['max'] === null || $jumlah <= $tier['max'])) {
                    $hargaSatuan = $tier['harga'];
                    break;
                }
            }

            return [
                'varian_nama' => null,
                'harga_satuan' => $hargaSatuan,
                'satuan_label' => $kategoriData['satuan_label'],
                'total' => $hargaSatuan * $jumlah,
                'custom' => false,
            ];
        }

        $item = collect($kategoriData['items'])->firstWhere('id', $varianId);

        if (! $item) {
            return [
                'varian_nama' => null,
                'harga_satuan' => 0,
                'satuan_label' => $kategoriData['satuan_label'],
                'total' => 0,
                'custom' => false,
            ];
        }

        $isCustom = ! empty($item['custom']);

        return [
            'varian_nama' => $item['nama'],
            'harga_satuan' => $isCustom ? 0 : $item['harga'],
            'satuan_label' => $kategoriData['satuan_label'],
            'total' => $isCustom ? 0 : $item['harga'] * $jumlah,
            'custom' => $isCustom,
        ];
    }

    public function beratSatuan(array $kategoriData, ?string $varianId): ?int
    {
        if ($kategoriData['pricing_mode'] === 'tiered') {
            return $kategoriData['berat'] ?? null;
        }

        $item = collect($kategoriData['items'])->firstWhere('id', $varianId);

        return $item['berat'] ?? null;
    }
}
