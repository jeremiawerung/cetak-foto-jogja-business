# Hasil Karya - Portofolio Beranda

Taruh foto hasil cetak/jepretan di folder ini dengan nama file **angka 1 sampai 8**, supaya otomatis muncul di section "Hasil Karya Kami" di halaman beranda:

- `1.jpg`
- `2.jpg`
- `3.jpg`
- `4.jpg`
- `5.jpg`
- `6.jpg`
- `7.jpg`
- `8.jpg`

Kalau salah satu file belum ada, kotak itu akan menampilkan placeholder (ikon gambar) — tidak akan error, jadi bisa diisi bertahap.

Rekomendasi: foto rasio persegi (1:1), minimal 600x600px, supaya rapi saat dipotong (crop) di grid galeri. Format yang didukung: `.jpg`. Kalau mau pakai `.png`/`.webp`, ubah baris `$fotoPortofolio = "images/portofolio/{$i}.jpg"` di `resources/views/home.blade.php` sesuai ekstensi yang dipakai.

Mau nambah lebih dari 8 foto? Ubah angka `8` pada `@for ($i = 1; $i <= 8; $i++)` di file yang sama.
