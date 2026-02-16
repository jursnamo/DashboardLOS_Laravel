<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiProxyController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'context' => ['nullable', 'string', 'max:6000'],
        ]);

        $prompt = implode("\n", [
            'Konteks Dashboard:',
            (string) ($payload['context'] ?? '-'),
            '',
            'Pertanyaan:',
            (string) $payload['question'],
        ]);

        $text = $this->runCloudflareAi(
            prompt: $prompt,
            systemPrompt: 'Anda asisten analis LOS dashboard. Jawab ringkas dalam bahasa Indonesia. Jika data kurang, jelaskan keterbatasannya.',
            model: config('services.cloudflare_ai.chat_model'),
            maxTokens: 240,
            temperature: 0.2,
            useDecisionToken: false
        );

        return response()->json([
            'answer' => $text,
        ]);
    }

    public function playbook(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'total_app' => ['nullable', 'integer', 'min:0'],
            'rows' => ['required', 'array', 'min:1', 'max:5'],
            'rows.*.status' => ['required', 'string', 'max:120'],
            'rows.*.impact_pct' => ['required', 'numeric', 'min:0'],
            'rows.*.app_count' => ['nullable', 'integer', 'min:0'],
            'rows.*.avg_tat_days' => ['nullable', 'numeric', 'min:0'],
            'rows.*.avg_step' => ['nullable', 'numeric', 'min:0'],
            'rows.*.priority' => ['nullable', 'string', 'max:24'],
            'outlier_rows' => ['nullable', 'array', 'max:10'],
            'outlier_rows.*.status' => ['required_with:outlier_rows', 'string', 'max:120'],
            'outlier_rows.*.avg' => ['nullable', 'numeric', 'min:0'],
            'outlier_rows.*.q1' => ['nullable', 'numeric', 'min:0'],
            'outlier_rows.*.med' => ['nullable', 'numeric', 'min:0'],
            'outlier_rows.*.q3' => ['nullable', 'numeric', 'min:0'],
            'outlier_rows.*.iqr' => ['nullable', 'numeric', 'min:0'],
            'outlier_rows.*.boundary' => ['nullable', 'numeric', 'min:0'],
            'outlier_rows.*.outlier' => ['nullable', 'integer', 'min:0'],
        ]);

        $rows = collect($payload['rows'])
            ->take(5)
            ->map(fn ($row) => [
                'status' => trim((string) ($row['status'] ?? '')),
                'impact_pct' => round((float) ($row['impact_pct'] ?? 0), 1),
                'app_count' => (int) ($row['app_count'] ?? 0),
                'avg_tat_days' => round((float) ($row['avg_tat_days'] ?? 0), 1),
                'avg_step' => round((float) ($row['avg_step'] ?? 0), 2),
                'priority' => trim((string) ($row['priority'] ?? '')),
            ])
            ->values()
            ->all();

        $outlierRows = collect($payload['outlier_rows'] ?? [])
            ->map(fn ($row) => [
                'status' => trim((string) ($row['status'] ?? '')),
                'avg' => round((float) ($row['avg'] ?? 0), 1),
                'q1' => round((float) ($row['q1'] ?? 0), 1),
                'med' => round((float) ($row['med'] ?? 0), 1),
                'q3' => round((float) ($row['q3'] ?? 0), 1),
                'iqr' => round((float) ($row['iqr'] ?? 0), 1),
                'boundary' => round((float) ($row['boundary'] ?? 0), 1),
                'outlier' => (int) ($row['outlier'] ?? 0),
            ])
            ->filter(fn ($row) => $row['status'] !== '')
            ->values();

        $outlierByStatus = [];
        foreach ($outlierRows as $row) {
            $outlierByStatus[strtolower($row['status'])] = $row;
        }

        $statusProfiles = collect($rows)
            ->take(3)
            ->map(function ($row) use ($outlierByStatus) {
                $key = strtolower($row['status']);
                $o = $outlierByStatus[$key] ?? null;
                return [
                    'status' => $row['status'],
                    'priority' => $row['priority'],
                    'impact_pct' => $row['impact_pct'],
                    'app_count' => $row['app_count'],
                    'avg_tat_days' => $row['avg_tat_days'],
                    'avg_step' => $row['avg_step'],
                    'outlier_count' => $o['outlier'] ?? 0,
                    'q3' => $o['q3'] ?? null,
                    'iqr' => $o['iqr'] ?? null,
                    'boundary' => $o['boundary'] ?? null,
                ];
            })
            ->values()
            ->all();

        $prompt = implode("\n", [
            'Anda analis proses kredit enterprise.',
            'Analisis wajib berbasis data per status (bukan template umum).',
            'Pertanyaan utama: bagaimana agar SLA turun untuk tiap status prioritas?',
            'Balas HANYA JSON array valid, TANPA markdown/code fence.',
            'Format wajib:',
            '[{"status":"...","action":"...","summary":"...","sla_target_impact":"...","reason":"..."}]',
            'Batasan:',
            '- output tepat 3 item (sesuai 3 status input)',
            '- action max 6 kata',
            '- summary max 20 kata',
            '- sla_target_impact max 10 kata',
            '- reason max 16 kata',
            '- gunakan bahasa Indonesia',
            '- summary harus menyebut minimal 2 angka dari data status tersebut',
            '- sla_target_impact harus berupa target terukur, contoh: "SLA breach -10% dalam 2 bulan"',
            '- reason jelaskan pemicu utama (tat/step/outlier/impact)',
            '- action harus fokus ke penurunan SLA breach/TAT',
            '',
            'Total app: '.((int) ($payload['total_app'] ?? 0)),
            'Status profiles (top 3): '.json_encode($statusProfiles, JSON_UNESCAPED_UNICODE),
        ]);

        $text = $this->runCloudflareAi(
            prompt: $prompt,
            systemPrompt: 'Anda analis proses kredit. Berikan rekomendasi action yang ringkas dan realistis.',
            model: config('services.cloudflare_ai.playbook_model'),
            maxTokens: 260,
            temperature: 0.2,
            useDecisionToken: true
        );

        return response()->json([
            'actions' => $this->parsePlaybookActions($text),
            'raw' => $text,
        ]);
    }

    private function runCloudflareAi(
        string $prompt,
        string $systemPrompt,
        ?string $model,
        int $maxTokens,
        float $temperature,
        bool $useDecisionToken
    ): string {
        $accountId = trim((string) config('services.cloudflare_ai.account_id'));
        $defaultToken = trim((string) config('services.cloudflare_ai.api_token'));
        $decisionToken = trim((string) config('services.cloudflare_ai.playbook_token'));
        $token = $useDecisionToken ? ($decisionToken !== '' ? $decisionToken : $defaultToken) : $defaultToken;
        $modelName = trim((string) $model);

        if ($accountId === '' || $token === '' || $modelName === '') {
            throw new HttpResponseException(response()->json([
                'message' => 'Cloudflare AI configuration missing. Please check .env values.',
            ], 503));
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$modelName}";

        $response = Http::timeout(30)
            ->withToken($token)
            ->acceptJson()
            ->post($url, [
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

        if (! $response->successful()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Cloudflare AI request failed.',
                'status' => $response->status(),
                'error' => $response->json() ?? $response->body(),
            ], $response->status()));
        }

        $data = $response->json();
        $text = $data['result']['response']
            ?? $data['result']['text']
            ?? $data['result']['output_text']
            ?? '';

        if (! is_string($text) || trim($text) === '') {
            throw new HttpResponseException(response()->json([
                'message' => 'Cloudflare AI returned empty response.',
            ], 502));
        }

        return $text;
    }

    private function parsePlaybookActions(string $text): array
    {
        $start = strpos($text, '[');
        $end = strrpos($text, ']');
        $hasFullArray = ($start !== false && $end !== false && $end > $start);
        $jsonText = $hasFullArray
            ? substr($text, $start, $end - $start + 1)
            : '';

        if (! $hasFullArray) {
            $decoded = $this->extractPartialJsonObjects($text);
        } else {
            try {
                $decoded = json_decode($jsonText, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $decoded = $this->extractPartialJsonObjects($text);
            }
        }

        if (is_array($decoded) && count($decoded) === 0 && str_contains($text, '{')) {
            // Fallback tambahan: model kadang kirim array rusak tapi object di tengah masih valid.
            $decoded = $this->extractPartialJsonObjects($text);
        }

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->take(3)
            ->map(fn ($x) => [
                'status' => trim((string) ($x['status'] ?? '')),
                'action' => trim((string) ($x['action'] ?? '')),
                'summary' => trim((string) ($x['summary'] ?? '')),
                'sla_target_impact' => trim((string) ($x['sla_target_impact'] ?? '')),
                'reason' => trim((string) ($x['reason'] ?? '')),
            ])
            ->values()
            ->all();
    }

    private function extractPartialJsonObjects(string $text): array
    {
        preg_match_all('/\{[^{}]*\}/', $text, $matches);
        $objects = $matches[0] ?? [];
        if (empty($objects)) {
            return [];
        }

        $rows = [];
        foreach ($objects as $obj) {
            try {
                $item = json_decode($obj, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($item)) {
                    $rows[] = $item;
                }
            } catch (\Throwable $e) {
                // Ignore malformed partial chunks.
            }
        }

        return $rows;
    }
}
