<?php

/*
|--------------------------------------------------------------------------
| Katalog Cetak Foto (LEGACY - hanya dipakai sekali oleh KategoriProdukSeeder)
|--------------------------------------------------------------------------
|
| Data katalog sekarang dikelola dari database lewat halaman admin
| /internal/kategori-produk (lihat App\Services\Katalog\KatalogBuilder dan
| App\Models\KategoriProduk). File ini tidak lagi dibaca langsung oleh
| aplikasi - dibiarkan hanya sebagai sumber data awal seeder.
|
*/

return [

    'pas_foto_paket' => [
        'label' => 'Pas Foto - Paket Hemat',
        'gambar' => 'images/katalog/pas_foto_paket.jpg',
        'pricing_mode' => 'varian',
        'satuan_label' => 'paket',
        'items' => [
            ['id' => 'paket_a', 'nama' => 'Paket A', 'harga' => 20000, 'harga_normal' => 22000, 'rincian' => '2x3: 12 lbr, 3x4: 5 lbr, 4x6: 4 lbr'],
            ['id' => 'paket_b', 'nama' => 'Paket B', 'harga' => 25000, 'harga_normal' => 26000, 'rincian' => '2x3: 6 lbr, 3x4: 10 lbr, 4x6: 8 lbr'],
            ['id' => 'paket_c', 'nama' => 'Paket C', 'harga' => 25000, 'harga_normal' => 28000, 'rincian' => '2x3: 18 lbr, 3x4: 5 lbr, 4x6: 4 lbr'],
            ['id' => 'paket_d', 'nama' => 'Paket D', 'harga' => 30000, 'harga_normal' => 32000, 'rincian' => '2x3: 12 lbr, 3x4: 15 lbr, 4x6: 4 lbr'],
        ],
    ],

    'pas_foto_satuan' => [
        'label' => 'Pas Foto - Satuan',
        'gambar' => 'images/katalog/pas_foto_satuan.jpg',
        'pricing_mode' => 'varian',
        'satuan_label' => 'paket',
        'catatan' => 'Cocok untuk kebutuhan pas foto umum, visa, pendaftaran haji/umroh, syarat menikah, ijazah, pendaftaran studi, dan syarat administrasi lainnya.',
        'items' => [
            ['id' => '4x6', 'nama' => '4x6', 'harga' => 15000, 'rincian' => 'Isi 12 foto per paket'],
            ['id' => '3x4', 'nama' => '3x4', 'harga' => 15000, 'rincian' => 'Isi 15 foto per paket'],
            ['id' => '2x3', 'nama' => '2x3', 'harga' => 15000, 'rincian' => 'Isi 15 foto per paket'],
        ],
    ],

    'jasa_editing' => [
        'label' => 'Jasa Editing Foto',
        'gambar' => 'images/katalog/jasa_editing.jpg',
        'pricing_mode' => 'varian',
        'satuan_label' => 'foto',
        'items' => [
            ['id' => 'ganti_background', 'nama' => 'Ganti Background', 'harga' => 5000, 'rincian' => 'per foto'],
            ['id' => 'ganti_baju', 'nama' => 'Ganti Baju', 'harga' => 10000, 'rincian' => 'per foto'],
            ['id' => 'ganti_jilbab', 'nama' => 'Ganti Jilbab', 'harga' => 10000, 'rincian' => 'per foto'],
        ],
    ],

    'cetak_reguler' => [
        'label' => 'Cetak Reguler',
        'gambar' => 'images/katalog/cetak_reguler.jpg',
        'pricing_mode' => 'varian',
        'satuan_label' => 'paket cetak',
        'items' => [
            ['id' => '2r', 'nama' => '2R (5,5x8,5 cm)', 'harga' => 10000, 'rincian' => 'Isi 5 foto per paket'],
            ['id' => '3r', 'nama' => '3R (8,3x12,8 cm)', 'harga' => 12000, 'rincian' => 'Isi 4 foto per paket'],
            ['id' => '4r', 'nama' => '4R (10,2x15,2 cm)', 'harga' => 20000, 'rincian' => 'Isi 5 foto per paket'],
            ['id' => '5r', 'nama' => '5R (12,7x17,8 cm)', 'harga' => 10000, 'rincian' => 'Isi 2 foto per paket'],
            ['id' => '6r', 'nama' => '6R (15,2x20,3 cm)', 'harga' => 20000, 'rincian' => 'Isi 3 foto per paket'],
            ['id' => '10r', 'nama' => '10R (20x25 cm)', 'harga' => 10000, 'rincian' => 'per foto'],
            ['id' => '10rw', 'nama' => '10Rw (20x30 cm)', 'harga' => 15000, 'rincian' => 'per foto'],
            ['id' => '12r', 'nama' => '12R (30x40 cm)', 'harga' => 50000, 'rincian' => 'per foto'],
            ['id' => '12rw', 'nama' => '12Rw (30x45 cm)', 'harga' => 60000, 'rincian' => 'per foto'],
            ['id' => '16r', 'nama' => '16R (40x50 cm)', 'harga' => 100000, 'rincian' => 'per foto'],
            ['id' => '16rw', 'nama' => '16Rw (40x60 cm)', 'harga' => 115000, 'rincian' => 'per foto'],
            ['id' => '20r', 'nama' => '20R (50x60 cm)', 'harga' => 125000, 'rincian' => 'per foto'],
            ['id' => '20rw', 'nama' => '20Rw (50x75 cm)', 'harga' => 140000, 'rincian' => 'per foto'],
            ['id' => '24r', 'nama' => '24R (60x90 cm)', 'harga' => 200000, 'rincian' => 'per foto'],
        ],
    ],

    'pigura' => [
        'label' => 'Pigura',
        'gambar' => 'images/katalog/pigura.jpg',
        'pricing_mode' => 'varian',
        'satuan_label' => 'pcs',
        'items' => [
            ['id' => '4r', 'nama' => '4R (10,2x15,2 cm)', 'harga' => 20000],
            ['id' => '5r', 'nama' => '5R (12,7x17,8 cm)', 'harga' => 30000],
            ['id' => '6r', 'nama' => '6R (15,2x20,3 cm)', 'harga' => 40000],
            ['id' => '10r', 'nama' => '10R (20x25 cm)', 'harga' => 50000],
            ['id' => '10rw', 'nama' => '10Rw (20x30 cm)', 'harga' => 60000],
            ['id' => '12r', 'nama' => '12R (30x40 cm)', 'harga' => 120000],
            ['id' => '12rw', 'nama' => '12Rw (30x45 cm)', 'harga' => 140000],
            ['id' => '16r', 'nama' => '16R (40x50 cm)', 'harga' => 280000],
            ['id' => '16rw', 'nama' => '16Rw (40x60 cm)', 'harga' => 300000],
            ['id' => '20r', 'nama' => '20R (50x60 cm)', 'harga' => 325000],
            ['id' => '20rw', 'nama' => '20Rw (50x75 cm)', 'harga' => 400000],
            ['id' => '24r', 'nama' => '24R (60x90 cm)', 'harga' => 500000],
            ['id' => 'custom', 'nama' => 'Custom Motif Frame', 'harga' => null, 'custom' => true, 'rincian' => 'Harga custom, hubungi admin'],
        ],
    ],

    'polaroid' => [
        'label' => 'Polaroid',
        'gambar' => 'images/katalog/polaroid.jpg',
        'pricing_mode' => 'tiered',
        'satuan_label' => 'foto',
        'deskripsi' => 'Ukuran 5,5x8,5 cm, rasio portrait, kertas doff Fujifilm.',
        'tiers' => [
            ['min' => 1, 'max' => 9, 'harga' => 2000],
            ['min' => 10, 'max' => 19, 'harga' => 1900],
            ['min' => 20, 'max' => null, 'harga' => 1800],
        ],
    ],

    'photo_strip' => [
        'label' => 'Photo Strip',
        'gambar' => 'images/katalog/photo_strip.jpg',
        'pricing_mode' => 'varian',
        'satuan_label' => 'strip',
        'deskripsi' => 'Ukuran 5x15 cm, rasio 1:1, 1 strip berisi 3 foto.',
        'items' => [
            ['id' => 'ecer', 'nama' => 'Ecer', 'harga' => 4000, 'rincian' => 'per strip'],
            ['id' => 'paket8', 'nama' => 'Paket 8 Strip', 'harga' => 30000, 'rincian' => 'per 8 strip'],
        ],
    ],

    'photo_square' => [
        'label' => 'Photo Square',
        'gambar' => 'images/katalog/photo_square.jpg',
        'pricing_mode' => 'varian',
        'satuan_label' => 'foto',
        'deskripsi' => 'Ukuran cetak 10x10 cm (area foto 9x9 cm), rasio 1:1.',
        'items' => [
            ['id' => 'ecer', 'nama' => 'Ecer', 'harga' => 5000, 'rincian' => 'per foto'],
            ['id' => 'paket6', 'nama' => 'Paket 6 Foto', 'harga' => 28000, 'rincian' => 'per 6 foto'],
        ],
    ],

    'photo_block' => [
        'label' => 'Photo Block',
        'gambar' => 'images/katalog/photo_block.jpg',
        'pricing_mode' => 'varian',
        'satuan_label' => 'pcs',
        'deskripsi' => 'Pigura minimalis tanpa kaca, sudah termasuk laminasi.',
        'items' => [
            ['id' => '20x25', 'nama' => '20x25 cm', 'harga' => 55000],
            ['id' => '20x30', 'nama' => '20x30 cm', 'harga' => 60000],
            ['id' => '30x40', 'nama' => '30x40 cm', 'harga' => 90000],
            ['id' => '30x45', 'nama' => '30x45 cm', 'harga' => 95000],
            ['id' => '40x60', 'nama' => '40x60 cm', 'harga' => 135000],
        ],
    ],

    'mini_canvas' => [
        'label' => 'Mini Canvas',
        'gambar' => 'images/katalog/mini_canvas.jpg',
        'pricing_mode' => 'varian',
        'satuan_label' => 'foto',
        'deskripsi' => 'Ukuran 10x7 cm, bahan kanvas, sudah termasuk spanram dan senderan kayu, bisa request desain custom.',
        'items' => [
            ['id' => 'kotak', 'nama' => 'Bentuk Kotak', 'harga' => 25000, 'rincian' => 'per foto'],
            ['id' => 'polygon', 'nama' => 'Bentuk Polygon', 'harga' => 25000, 'rincian' => 'per foto'],
        ],
    ],

];
