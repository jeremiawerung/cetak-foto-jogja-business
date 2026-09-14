<?php

namespace App\Services\KartuPelajar;

class AuditReportBuilder
{
    /**
     * @param  list<RowAuditResult>  $results
     * @return array<string, int>
     */
    public function summarize(array $results): array
    {
        $summary = [
            'clean' => 0,
            'normalized_with_warning' => 0,
            'needs_manual_review' => 0,
            'duplicate_collapsed' => 0,
            'total' => count($results),
        ];

        foreach ($results as $result) {
            $summary[$result->status] = ($summary[$result->status] ?? 0) + 1;
        }

        return $summary;
    }

    /**
     * @param  list<RowAuditResult>  $results
     */
    public function toCsv(array $results): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Gagal membuat buffer CSV');
        }

        $header = array_keys((new RowAuditResult('', 0, [], 'clean', []))->toCsvRow());
        fputcsv($handle, $header);

        foreach ($results as $result) {
            fputcsv($handle, $result->toCsvRow());
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }
}
