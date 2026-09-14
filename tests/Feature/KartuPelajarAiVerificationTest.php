<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\RowAuditResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KartuPelajarAiVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_to_index_when_no_audit_session_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('internal.kartu-pelajar.verifikasi-ai'));

        $response->assertRedirect(route('internal.kartu-pelajar.index'));
    }

    public function test_shows_error_when_api_key_not_configured(): void
    {
        config(['services.kartu_pelajar.ai_verification.api_key' => null]);

        $user = User::factory()->create();
        $results = [$this->makeCleanResult(1)];

        session([
            'kartu_pelajar.results.excel' => $results,
            'kartu_pelajar.results.gform' => $results,
        ]);

        $response = $this->actingAs($user)->post(route('internal.kartu-pelajar.verifikasi-ai'));

        $response->assertSessionHasErrors();
    }

    public function test_merges_ai_opinions_into_results_view_when_configured(): void
    {
        config(['services.kartu_pelajar.ai_verification.api_key' => 'fake-test-key']);

        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '{"results": [{"row": 1, "flagged": true, "note": "Nama terlihat tidak lengkap"}]}',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $results = [$this->makeCleanResult(1)];

        session([
            'kartu_pelajar.results.excel' => $results,
            'kartu_pelajar.results.gform' => $results,
        ]);

        $response = $this->actingAs($user)->post(route('internal.kartu-pelajar.verifikasi-ai'));

        $response->assertOk();
        $response->assertViewHas('excelOpinions', fn ($opinions) => $opinions[1]['flagged'] === true);
        $response->assertSee('Nama terlihat tidak lengkap');
    }

    private function makeCleanResult(int $rowNumber): RowAuditResult
    {
        return new RowAuditResult(
            'excel',
            $rowNumber,
            ['nama' => FieldResult::clean('Contoh Siswa')],
            'clean',
            []
        );
    }
}
