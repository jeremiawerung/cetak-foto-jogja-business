<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\DuplicateDetector;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\RowAuditResult;
use PHPUnit\Framework\TestCase;

class DuplicateDetectorTest extends TestCase
{
    private function makeResult(int $rowNumber, string $nama): RowAuditResult
    {
        return new RowAuditResult(
            'gform',
            $rowNumber,
            ['nama' => FieldResult::clean($nama)],
            'clean',
            []
        );
    }

    public function test_submissions_seconds_apart_are_collapsed_as_exact_duplicate(): void
    {
        $detector = new DuplicateDetector(thresholdSeconds: 5);

        $rows = [
            ['timestamp' => '2026-08-11 15:25:48', 'result' => $this->makeResult(10, 'Fania Rizki Romadona')],
            ['timestamp' => '2026-08-11 15:25:50', 'result' => $this->makeResult(11, 'Fania Rizki Romadona')],
        ];

        $results = $detector->annotate($rows);

        $this->assertSame('clean', $results[0]->status);
        $this->assertSame('duplicate_collapsed', $results[1]->status);
        $this->assertSame(10, $results[1]->duplicateOfRow);
    }

    public function test_submissions_far_apart_are_flagged_as_candidates_not_collapsed(): void
    {
        $detector = new DuplicateDetector(thresholdSeconds: 5);

        // Kasus nyata dari sample: nama sama muncul lagi ~14m39s kemudian.
        $rows = [
            ['timestamp' => '2026-08-11 17:42:43', 'result' => $this->makeResult(20, 'Esa Mahendra Ibnun Purwanto')],
            ['timestamp' => '2026-08-11 17:57:22', 'result' => $this->makeResult(21, 'Esa Mahendra Ibnun Purwanto')],
        ];

        $results = $detector->annotate($rows);

        $this->assertSame('normalized_with_warning', $results[0]->status);
        $this->assertSame('normalized_with_warning', $results[1]->status);
        $this->assertNull($results[0]->duplicateOfRow);
        $this->assertNull($results[1]->duplicateOfRow);
    }

    public function test_different_names_are_not_compared(): void
    {
        $detector = new DuplicateDetector(thresholdSeconds: 5);

        $rows = [
            ['timestamp' => '2026-08-11 15:25:48', 'result' => $this->makeResult(10, 'Fania Rizki Romadona')],
            ['timestamp' => '2026-08-11 15:25:50', 'result' => $this->makeResult(11, 'Ahmad Rizki Luthfiansyah')],
        ];

        $results = $detector->annotate($rows);

        $this->assertSame('clean', $results[0]->status);
        $this->assertSame('clean', $results[1]->status);
    }
}
