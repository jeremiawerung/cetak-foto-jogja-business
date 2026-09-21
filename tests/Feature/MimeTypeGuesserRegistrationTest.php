<?php

namespace Tests\Feature;

use Symfony\Component\Mime\MimeTypes;
use Tests\TestCase;

/**
 * AppServiceProvider mendaftarkan SignatureMimeTypeGuesser supaya validasi `mimes:` di
 * semua form upload tidak fatal error di hosting yang ext-fileinfo/proc_open-nya tidak
 * bisa diandalkan (lihat komentar di provider itu). Test unit murni pada guesser-nya
 * ada di tests/Unit/SignatureMimeTypeGuesserTest.php - test ini khusus memastikan
 * guesser itu benar-benar KEPASANG lewat boot aplikasi yang sesungguhnya, dan bekerja
 * lewat instance global Symfony\Component\Mime\MimeTypes yang dipakai Laravel.
 */
class MimeTypeGuesserRegistrationTest extends TestCase
{
    public function test_signature_guesser_is_registered_and_recognizes_a_real_pdf(): void
    {
        $path = base_path('Sample/SMPN 15 YOGYAKARTA/DATA/profil kartu pelajar 2026.pdf');

        if (! is_file($path)) {
            $this->markTestSkipped('File sample PDF tidak ditemukan.');
        }

        $this->assertSame('application/pdf', MimeTypes::getDefault()->guessMimeType($path));
    }

    public function test_guessing_an_unrecognizable_file_does_not_throw(): void
    {
        // Di mesin ini kemungkinan ext-fileinfo masih tersedia sebagai fallback dan akan
        // menebak sesuatu (mis. "text/plain") - yang penting cuma dipastikan tidak
        // melempar LogicException seperti yang terjadi di hosting produksi.
        $path = tempnam(sys_get_temp_dir(), 'mime-reg-test-');
        file_put_contents($path, 'bukan file yang dikenali');

        MimeTypes::getDefault()->guessMimeType($path);

        unlink($path);
        $this->addToAssertionCount(1);
    }
}
