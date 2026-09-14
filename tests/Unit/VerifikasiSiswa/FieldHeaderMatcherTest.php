<?php

namespace Tests\Unit\VerifikasiSiswa;

use App\Services\VerifikasiSiswa\FieldHeaderMatcher;
use PHPUnit\Framework\TestCase;

class FieldHeaderMatcherTest extends TestCase
{
    private const DEFAULT_DEFINITIONS = [
        ['key' => 'jenis_kelamin', 'aturan' => [['jk'], ['jenis', 'kelamin']]],
        ['key' => 'nisn', 'aturan' => [['nisn']]],
        ['key' => 'nis', 'aturan' => [['nis']]],
        ['key' => 'tanggal_lahir', 'aturan' => [['tanggal', 'lahir']]],
        ['key' => 'tempat_lahir', 'aturan' => [['tempat', 'lahir']]],
        ['key' => 'alamat', 'aturan' => [['alamat']]],
        ['key' => 'agama', 'aturan' => [['agama']]],
        ['key' => 'kelas', 'aturan' => [['rombel'], ['kelas']]],
        ['key' => 'nama', 'aturan' => [['nama']]],
    ];

    public function test_jenis_kelamin_not_mistaken_for_nis(): void
    {
        $matcher = new FieldHeaderMatcher;

        $this->assertSame('jenis_kelamin', $matcher->matchHeader('Jenis Kelamin', self::DEFAULT_DEFINITIONS));
        $this->assertSame('jenis_kelamin', $matcher->matchHeader('JK', self::DEFAULT_DEFINITIONS));
    }

    public function test_nisn_checked_before_nis(): void
    {
        $matcher = new FieldHeaderMatcher;

        $this->assertSame('nisn', $matcher->matchHeader('NISN', self::DEFAULT_DEFINITIONS));
        $this->assertSame('nis', $matcher->matchHeader('NIS', self::DEFAULT_DEFINITIONS));
    }

    public function test_combined_header_with_punctuation_still_matches_via_and_group(): void
    {
        $matcher = new FieldHeaderMatcher;

        // Real-world case: "Tempat, Tanggal Lahir" - kata "tempat" dan "tanggal" tidak
        // bersebelahan langsung karena ada koma, jadi keyword satu-frasa akan gagal,
        // tapi aturan AND-group (kata "tanggal" dan "lahir" harus ada, di mana saja)
        // tetap kena.
        $this->assertSame('tanggal_lahir', $matcher->matchHeader('Tempat, Tanggal Lahir', self::DEFAULT_DEFINITIONS));
    }

    public function test_unrecognized_header_returns_null(): void
    {
        $matcher = new FieldHeaderMatcher;

        $this->assertNull($matcher->matchHeader('Nomor Sekolah', self::DEFAULT_DEFINITIONS));
    }

    public function test_custom_field_keyword_is_matched_when_registered(): void
    {
        $matcher = new FieldHeaderMatcher;
        $definitions = array_merge(self::DEFAULT_DEFINITIONS, [
            ['key' => 'nomor_sekolah', 'aturan' => [['nomor', 'sekolah']]],
        ]);

        $this->assertSame('nomor_sekolah', $matcher->matchHeader('Nomor Sekolah', $definitions));
    }

    public function test_build_column_map_returns_first_occurrence_per_field(): void
    {
        $matcher = new FieldHeaderMatcher;

        $map = $matcher->buildColumnMap(['Nama', 'Kelas', 'JK', 'NISN'], self::DEFAULT_DEFINITIONS);

        $this->assertSame(['nama' => 0, 'kelas' => 1, 'jenis_kelamin' => 2, 'nisn' => 3], $map);
    }

    public function test_build_column_map_skips_unrecognized_columns(): void
    {
        $matcher = new FieldHeaderMatcher;

        $map = $matcher->buildColumnMap(['Nama', 'Nomor Sekolah', 'Kelas'], self::DEFAULT_DEFINITIONS);

        $this->assertSame(['nama' => 0, 'kelas' => 2], $map);
    }
}
