<?php

namespace App\Services\KartuPelajar;

class DuplicateDetector
{
    public function __construct(
        private readonly int $thresholdSeconds = 5,
    ) {}

    /**
     * @param  list<array{timestamp: string, result: RowAuditResult}>  $rows  hanya untuk sumber yang punya timestamp (GForm)
     * @return list<RowAuditResult>
     */
    public function annotate(array $rows): array
    {
        $groups = [];

        foreach ($rows as $i => $row) {
            $name = mb_strtoupper(trim((string) $row['result']->fieldValue('nama')));
            $groups[$name][] = ['index' => $i, 'timestamp' => $row['timestamp'], 'result' => $row['result']];
        }

        $collapsedIndexes = [];

        foreach ($groups as $name => $entries) {
            if ($name === '' || count($entries) < 2) {
                continue;
            }

            usort($entries, fn ($a, $b) => strcmp($a['timestamp'], $b['timestamp']));

            for ($i = 1; $i < count($entries); $i++) {
                $prev = $entries[$i - 1];
                $curr = $entries[$i];

                if (isset($collapsedIndexes[$curr['index']])) {
                    continue;
                }

                $diff = abs(strtotime($curr['timestamp']) - strtotime($prev['timestamp']));

                if ($diff <= $this->thresholdSeconds) {
                    $curr['result']->status = 'duplicate_collapsed';
                    $curr['result']->duplicateOfRow = $prev['result']->rowNumber;
                    $curr['result']->reasons[] = sprintf(
                        'Duplikat exact dari baris #%d (selisih %ds) — otomatis digabung',
                        $prev['result']->rowNumber,
                        $diff
                    );
                    $collapsedIndexes[$curr['index']] = true;

                    continue;
                }

                if (! isset($collapsedIndexes[$prev['index']])) {
                    $prev['result']->reasons[] = sprintf(
                        'Kandidat duplikat: nama sama muncul juga di baris #%d (selisih %s) — perlu review manual',
                        $curr['result']->rowNumber,
                        $this->formatDiff($diff)
                    );
                    if ($prev['result']->status === 'clean') {
                        $prev['result']->status = 'normalized_with_warning';
                    }
                }

                $curr['result']->reasons[] = sprintf(
                    'Kandidat duplikat: nama sama muncul juga di baris #%d (selisih %s) — perlu review manual',
                    $prev['result']->rowNumber,
                    $this->formatDiff($diff)
                );
                if ($curr['result']->status === 'clean') {
                    $curr['result']->status = 'normalized_with_warning';
                }
            }
        }

        return array_map(fn ($row) => $row['result'], $rows);
    }

    private function formatDiff(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} detik";
        }

        return '~'.intdiv($seconds, 60).' menit';
    }
}
