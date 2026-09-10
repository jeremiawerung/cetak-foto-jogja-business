<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirtableLogger
{
    /**
     * Kirim satu baris data ke Airtable sebagai log/rekap.
     * Dibuat aman untuk gagal: kalau Airtable belum dikonfigurasi atau error,
     * proses order/booking utama tetap lanjut tanpa terganggu.
     */
    public function log(string $table, array $fields): void
    {
        $token = config('services.airtable.token');
        $baseId = config('services.airtable.base_id');

        if (! $token || ! $baseId || ! $table) {
            return;
        }

        try {
            Http::withToken($token)
                ->timeout(5)
                ->asJson()
                ->post('https://api.airtable.com/v0/'.$baseId.'/'.rawurlencode($table), [
                    'fields' => $fields,
                ])
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim log ke Airtable: '.$e->getMessage());
        }
    }
}
