<?php

namespace App\Services\KartuPelajar;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiRowVerifier
{
    private readonly ?string $apiKey;

    private readonly string $model;

    private readonly int $batchSize;

    public function __construct(?string $apiKey = null, ?string $model = null, ?int $batchSize = null)
    {
        $this->apiKey = $apiKey ?? config('services.kartu_pelajar.ai_verification.api_key');
        $this->model = $model ?? config('services.kartu_pelajar.ai_verification.model', 'deepseek-flash');
        $this->batchSize = $batchSize ?? config('services.kartu_pelajar.ai_verification.batch_size', 25);
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * @param  list<RowAuditResult>  $results  hanya baris yang layak diverifikasi (status clean/normalized_with_warning)
     * @return array<int, array{flagged: bool, note: ?string}> hasil, keyed by rowNumber
     */
    public function verifyBatch(array $results): array
    {
        if ($results === []) {
            return [];
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('AI verification belum dikonfigurasi: DEEPSEEK_API_KEY belum diisi di .env');
        }

        $opinions = [];

        foreach (array_chunk($results, $this->batchSize) as $chunk) {
            $opinions += $this->verifyChunk($chunk);
        }

        return $opinions;
    }

    /**
     * @param  list<RowAuditResult>  $chunk
     * @return array<int, array{flagged: bool, note: ?string}>
     */
    private function verifyChunk(array $chunk): array
    {
        $rowsPayload = array_map(fn (RowAuditResult $r) => [
            'row' => $r->rowNumber,
            'nama' => $r->fieldValue('nama'),
            'kelas' => $r->fieldValue('kelas'),
            'tanggal_lahir' => $r->fieldValue('tanggal_lahir'),
            'nis' => $r->fieldValue('nis'),
            'nisn' => $r->fieldValue('nisn'),
            'alamat' => $r->fieldValue('alamat'),
        ], $chunk);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.deepseek.com/chat/completions', [
            'model' => $this->model,
            'max_tokens' => 4096,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'user', 'content' => $this->buildPrompt($rowsPayload)],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Panggilan API AI gagal (HTTP '.$response->status().'): '.$response->body());
        }

        $finishReason = $response->json('choices.0.finish_reason');

        if ($finishReason !== 'stop') {
            throw new RuntimeException("Respons AI terpotong sebelum selesai (finish_reason: {$finishReason}). Coba kurangi KARTU_PELAJAR_AI_BATCH_SIZE di .env.");
        }

        return $this->parseOpinions((string) $response->json('choices.0.message.content', ''));
    }

    /**
     * @param  list<array<string, ?string>>  $rowsPayload
     */
    private function buildPrompt(array $rowsPayload): string
    {
        $json = json_encode($rowsPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
            Kamu membantu admin sekolah memeriksa data biodata siswa untuk pembuatan kartu pelajar.
            Baris-baris berikut SUDAH lolos validasi format otomatis. Tugasmu memeriksa apakah datanya
            masuk akal secara logis/konten (bukan format), misalnya:
            - Nama yang terlihat tidak lengkap, aneh, atau typo yang mencurigakan.
            - Tanggal lahir yang tidak wajar untuk usia siswa SMP.
            - Alamat yang terlihat terpotong atau tidak lengkap walau lolos validasi format.
            - Kombinasi data yang janggal (misalnya kelas dan usia tidak selaras).

            Data (JSON):
            {$json}

            Balas HANYA dengan JSON object tanpa teks lain, format persis seperti ini:
            {"results": [{"row": <nomor_baris>, "flagged": <true/false>, "note": "<alasan singkat jika flagged, kosongkan jika tidak>"}]}
            PROMPT;
    }

    /**
     * @return array<int, array{flagged: bool, note: ?string}>
     */
    private function parseOpinions(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? $text);

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            return [];
        }

        // Terima baik bentuk objek {"results": [...]} maupun array langsung [...],
        // supaya tidak terikat ke satu format respons dari satu provider tertentu.
        $items = $decoded['results'] ?? $decoded;

        if (! is_array($items)) {
            return [];
        }

        $opinions = [];

        foreach ($items as $item) {
            if (! isset($item['row'])) {
                continue;
            }

            $note = $item['note'] ?? null;

            $opinions[(int) $item['row']] = [
                'flagged' => (bool) ($item['flagged'] ?? false),
                'note' => ($note === '' ? null : $note),
            ];
        }

        return $opinions;
    }
}
