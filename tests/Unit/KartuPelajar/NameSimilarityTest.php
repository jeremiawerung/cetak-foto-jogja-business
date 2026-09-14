<?php

namespace Tests\Unit\KartuPelajar;

use App\Services\KartuPelajar\NameSimilarity;
use PHPUnit\Framework\TestCase;

class NameSimilarityTest extends TestCase
{
    public function test_identical_names_score_one(): void
    {
        $this->assertSame(1.0, NameSimilarity::score('Adinda Nindya Kirana', 'Adinda Nindya Kirana'));
    }

    public function test_case_insensitive(): void
    {
        $this->assertSame(1.0, NameSimilarity::score('ADINDA NINDYA', 'adinda nindya'));
    }

    public function test_minor_typo_scores_high_but_not_perfect(): void
    {
        $score = NameSimilarity::score('Muhammad Rizki', 'Muhamad Rizki');

        $this->assertGreaterThan(0.85, $score);
        $this->assertLessThan(1.0, $score);
    }

    public function test_completely_different_names_score_low(): void
    {
        $score = NameSimilarity::score('Adinda Nindya Kirana', 'Bagas Setiawan Putra');

        $this->assertLessThan(0.4, $score);
    }

    public function test_empty_strings_score_zero_against_non_empty(): void
    {
        $this->assertSame(0.0, NameSimilarity::score('', 'Adinda'));
        $this->assertSame(0.0, NameSimilarity::score('Adinda', ''));
    }

    public function test_both_empty_scores_one(): void
    {
        $this->assertSame(1.0, NameSimilarity::score('', ''));
    }
}
