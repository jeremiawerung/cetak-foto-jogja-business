<?php

namespace App\Services\VerifikasiSiswa;

use Google\Client;
use Google\Service\Sheets;
use RuntimeException;

/**
 * Wrapper tipis di atas google/apiclient khusus untuk baca nilai sel (spreadsheets.values.get)
 * — autentikasi via Service Account (cocok untuk job berkala tanpa interaksi user).
 */
class GoogleSheetsClient
{
    private ?Sheets $service = null;

    public function isConfigured(): bool
    {
        $credentialsPath = config('services.verifikasi_siswa.google_service_account_path');

        return is_string($credentialsPath) && is_file($credentialsPath);
    }

    /**
     * @return list<list<string>> baris demi baris, tiap baris list nilai kolom (string)
     */
    public function fetchRows(string $spreadsheetId, string $range): array
    {
        $response = $this->service()->spreadsheets_values->get($spreadsheetId, $range);

        /** @var list<list<mixed>> $values */
        $values = $response->getValues() ?? [];

        return array_map(
            fn (array $row) => array_map(fn ($cell) => trim((string) $cell), $row),
            $values,
        );
    }

    public function firstSheetTitle(string $spreadsheetId): string
    {
        $sheets = $this->service()->spreadsheets->get($spreadsheetId)->getSheets();

        if ($sheets === [] || $sheets === null) {
            throw new RuntimeException("Spreadsheet {$spreadsheetId} tidak punya sheet sama sekali.");
        }

        return $sheets[0]->getProperties()->getTitle();
    }

    private function service(): Sheets
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $credentialsPath = config('services.verifikasi_siswa.google_service_account_path');

        if (! is_string($credentialsPath) || ! is_file($credentialsPath)) {
            throw new RuntimeException("File kredensial Service Account Google tidak ditemukan di: {$credentialsPath}");
        }

        $client = new Client;
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS_READONLY);

        return $this->service = new Sheets($client);
    }
}
