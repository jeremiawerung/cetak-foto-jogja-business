<?php

namespace App\Services\KartuPelajar;

use App\Models\KartuPelajarBatch;
use App\Models\KartuPelajarVisualFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Cocokkan file kartu hasil Photoshop (dinamai sesuai ID_FILE, mis. "KP-000123.psd")
 * terhadap daftar siswa yang seharusnya ada di batch ini (hasil ExportBuilder) — deteksi
 * yang HILANG (ID_FILE tanpa file) dan TIDAK DIKENALI (file tanpa ID_FILE yang cocok).
 *
 * Hasil Photoshop asli berformat .psd (bukan gambar biasa) — browser tidak bisa
 * menampilkannya langsung, jadi thumbnail JPEG yang SUDAH tertanam di dalam file .psd
 * itu sendiri diekstrak (lihat PsdThumbnailExtractor) supaya tetap bisa dipratinjau.
 */
class VisualQaChecker
{
    public function __construct(
        private readonly ExportBuilder $exportBuilder = new ExportBuilder,
        private readonly PsdThumbnailExtractor $thumbnailExtractor = new PsdThumbnailExtractor,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return array{matched: int, orphan: int}
     */
    public function ingest(KartuPelajarBatch $batch, array $files): array
    {
        $expectedIdFiles = collect($this->exportBuilder->buildPreview($batch))->pluck('id_file')->flip();

        $matched = 0;
        $orphan = 0;

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $idFile = $this->extractIdFile($originalName);

            $status = ($idFile !== null && $expectedIdFiles->has($idFile)) ? 'matched' : 'orphan';

            $storedPath = $file->store("kartu-pelajar/visual-qa/batch-{$batch->id}", 'public');
            $thumbnailPath = $this->extractAndStoreThumbnail($batch, $file);

            KartuPelajarVisualFile::create([
                'batch_id' => $batch->id,
                'id_file' => $idFile,
                'original_filename' => $originalName,
                'stored_path' => $storedPath,
                'thumbnail_path' => $thumbnailPath,
                'status' => $status,
            ]);

            $status === 'matched' ? $matched++ : $orphan++;
        }

        return ['matched' => $matched, 'orphan' => $orphan];
    }

    private function extractAndStoreThumbnail(KartuPelajarBatch $batch, UploadedFile $file): ?string
    {
        if (mb_strtolower($file->getClientOriginalExtension()) !== 'psd') {
            return null;
        }

        $jpegData = $this->thumbnailExtractor->extract($file->getRealPath());

        if ($jpegData === null) {
            return null;
        }

        $path = "kartu-pelajar/visual-qa/batch-{$batch->id}/thumbnails/".Str::random(40).'.jpg';
        Storage::disk('public')->put($path, $jpegData);

        return $path;
    }

    /**
     * @return array{ditemukan: Collection, hilang: list<string>, tidak_dikenali: Collection}
     */
    public function report(KartuPelajarBatch $batch): array
    {
        $expectedIdFiles = collect($this->exportBuilder->buildPreview($batch))->pluck('id_file');

        $uploaded = $batch->visualFiles()->get();

        $ditemukan = $uploaded->where('status', 'matched');
        $tidakDikenali = $uploaded->where('status', 'orphan');

        $uploadedIdFiles = $ditemukan->pluck('id_file')->unique();
        $hilang = $expectedIdFiles->diff($uploadedIdFiles)->values()->all();

        return [
            'ditemukan' => $ditemukan,
            'hilang' => $hilang,
            'tidak_dikenali' => $tidakDikenali,
        ];
    }

    private function extractIdFile(string $filename): ?string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        return preg_match('/^KP-\d{6}$/', $base) ? $base : null;
    }
}
