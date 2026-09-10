# Gambar Contoh - Katalog Cetak Foto

Taruh file gambar di folder ini dengan nama file **persis** seperti berikut supaya otomatis muncul di halaman katalog (`/cetak-foto`):

- `pas_foto_paket.jpg`
- `pas_foto_satuan.jpg`
- `jasa_editing.jpg`
- `cetak_reguler.jpg`
- `pigura.jpg`
- `polaroid.jpg`
- `photo_strip.jpg`
- `photo_square.jpg`
- `photo_block.jpg`
- `mini_canvas.jpg`

Kalau file belum ada, halaman akan menampilkan kotak placeholder (ikon gambar) sebagai gantinya — tidak akan error.

Format yang didukung: `.jpg`. Kalau mau pakai `.png` atau `.webp`, ubah juga nilai `'gambar' => ...` di `config/catalog.php` untuk kategori terkait.

Rekomendasi ukuran: persegi (misal 400x400px), supaya rapi saat dipotong (crop) di kartu katalog.
