<?php

namespace App\Console\Commands;

use App\Models\VerifikasiSiswaProyek;
use App\Services\VerifikasiSiswa\ActivityLogger;
use App\Services\VerifikasiSiswa\GFormSyncer;
use Illuminate\Console\Command;

class SyncGFormResponses extends Command
{
    protected $signature = 'verifikasi-siswa:sync {proyek? : ID proyek spesifik, kosongkan untuk semua proyek yang punya google_sheet_id}';

    protected $description = 'Ambil respons Google Form baru (via Sheets API) dan cocokkan ke roster siswa';

    public function handle(GFormSyncer $syncer, ActivityLogger $logger): int
    {
        $proyekId = $this->argument('proyek');

        $query = VerifikasiSiswaProyek::query()->whereNotNull('google_sheet_id');

        if ($proyekId !== null) {
            $query->where('id', $proyekId);
        }

        $proyeks = $query->get();

        if ($proyeks->isEmpty()) {
            $this->info('Tidak ada proyek dengan google_sheet_id yang perlu disinkron.');

            return self::SUCCESS;
        }

        foreach ($proyeks as $proyek) {
            $result = $syncer->sync($proyek);

            if ($result['jumlah_baru'] === 0) {
                $this->info("Proyek \"{$proyek->nama}\": tidak ada respons baru.");

                continue;
            }

            $logger->log($proyek, 'sync', $result['jumlah_baru'].' respons baru disinkron dari Google Form', [
                'jumlah_baru' => $result['jumlah_baru'],
                'status_setelah_cocok' => $result['status'],
            ]);

            $this->info("Proyek \"{$proyek->nama}\": {$result['jumlah_baru']} respons baru, status: ".json_encode($result['status']));
        }

        return self::SUCCESS;
    }
}
