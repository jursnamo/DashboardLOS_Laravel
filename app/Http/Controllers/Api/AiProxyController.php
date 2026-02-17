<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ai\DeterministicSimulationSummary;
use App\Services\Ai\SimulationIntentDetector;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AiProxyController extends Controller
{
    public function detectIntent(Request $request, SimulationIntentDetector $detector): JsonResponse
    {
        $payload = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        return response()->json($detector->detect((string) $payload['question']));
    }

    public function chat(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'context' => ['nullable', 'string', 'max:6000'],
            'provider' => ['nullable', 'in:cloudflare,gemini'],
        ]);
        $provider = $this->resolveProvider($payload['provider'] ?? null);

        $prompt = implode("\n", [
            'Konteks Dashboard:',
            (string) ($payload['context'] ?? '-'),
            '',
            'Pertanyaan:',
            (string) $payload['question'],
        ]);

        $text = $this->runAiByProvider(
            provider: $provider,
            prompt: $prompt,
            systemPrompt: 'Anda asisten analis LOS dashboard. Jawab ringkas dalam bahasa Indonesia. Jika data kurang, jelaskan keterbatasannya.',
            model: $provider === 'gemini'
                ? config('services.gemini_ai.chat_model')
                : config('services.cloudflare_ai.chat_model'),
            maxTokens: 480,
            temperature: 0.2,
            useDecisionToken: false
        );

        if ($this->looksTruncated($text)) {
            $continuationPrompt = implode("\n", [
                'Lanjutkan jawaban berikut agar selesai dan utuh.',
                'Jangan mengulang dari awal.',
                'Tulis lanjutan maksimal 3 kalimat dalam bahasa Indonesia profesional.',
                '',
                'Jawaban sebelumnya:',
                $text,
                '',
                'Pertanyaan user:',
                (string) $payload['question'],
            ]);

            $tail = $this->runAiByProvider(
                provider: $provider,
                prompt: $continuationPrompt,
                systemPrompt: 'Anda asisten analis LOS dashboard. Lengkapi jawaban yang terputus dengan kalimat final yang utuh.',
                model: $provider === 'gemini'
                    ? config('services.gemini_ai.chat_model')
                    : config('services.cloudflare_ai.chat_model'),
                maxTokens: 140,
                temperature: 0.2,
                useDecisionToken: false
            );

            if (trim($tail) !== '') {
                $text = rtrim($text)." ".ltrim($tail);
            }
        }

        return response()->json([
            'answer' => $text,
        ]);
    }

    public function simulationInsight(
        Request $request,
        DeterministicSimulationSummary $summaryBuilder
    ): JsonResponse {
        $payload = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'provider' => ['nullable', 'in:cloudflare,gemini'],
            'status' => ['nullable', 'string', 'max:120'],
            'value' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'was_clamped' => ['nullable', 'boolean'],
            'original_value' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'targets' => ['nullable', 'array', 'min:1', 'max:6'],
            'targets.*.status' => ['required_with:targets', 'string', 'max:120'],
            'targets.*.value' => ['required_with:targets', 'numeric', 'min:0', 'max:1000'],
            'targets.*.was_clamped' => ['nullable', 'boolean'],
            'targets.*.original_value' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'simulator_result' => ['required', 'array'],
            'simulator_result.baseline_sla' => ['required', 'numeric'],
            'simulator_result.projected_sla' => ['required', 'numeric'],
            'simulator_result.avg_tat_before' => ['required', 'numeric'],
            'simulator_result.avg_tat_after' => ['required', 'numeric'],
            'simulator_result.tat_delta' => ['required', 'numeric'],
            'simulator_result.total_saving_days' => ['required', 'numeric'],
            'simulator_result.breach_delta' => ['required', 'numeric'],
        ]);

        $provider = $this->resolveProvider($payload['provider'] ?? null);
        $targets = collect((array) ($payload['targets'] ?? []))
            ->map(fn ($x) => [
                'status' => trim((string) ($x['status'] ?? '')),
                'value' => (int) round((float) ($x['value'] ?? 0)),
                'original_value' => isset($x['original_value']) ? (int) round((float) $x['original_value']) : (int) round((float) ($x['value'] ?? 0)),
                'was_clamped' => (bool) ($x['was_clamped'] ?? false),
            ])
            ->filter(fn ($x) => $x['status'] !== '')
            ->values()
            ->all();

        if (empty($targets)) {
            $singleStatus = trim((string) ($payload['status'] ?? ''));
            if ($singleStatus === '' || !isset($payload['value'])) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Payload simulasi tidak valid: butuh status+value atau targets[].',
                ], 422));
            }
            $singleValue = (int) round((float) ($payload['value'] ?? 0));
            $targets[] = [
                'status' => $singleStatus,
                'value' => $singleValue,
                'original_value' => isset($payload['original_value']) ? (int) round((float) $payload['original_value']) : $singleValue,
                'was_clamped' => (bool) ($payload['was_clamped'] ?? false),
            ];
        }

        $statusLabel = implode(', ', array_map(
            fn ($x) => "{$x['status']} {$x['value']}%",
            $targets
        ));
        if (mb_strlen($statusLabel) > 120) {
            $statusLabel = mb_substr($statusLabel, 0, 120);
        }

        $requestedValue = (int) round(array_sum(array_map(fn ($x) => (int) ($x['original_value'] ?? 0), $targets)) / max(1, count($targets)));
        $appliedValue = (int) round(array_sum(array_map(fn ($x) => (int) ($x['value'] ?? 0), $targets)) / max(1, count($targets)));
        $hasClamped = count(array_filter($targets, fn ($x) => (bool) ($x['was_clamped'] ?? false))) > 0;
        $sim = (array) ($payload['simulator_result'] ?? []);
        $systemSummary = $summaryBuilder->generateCombined($targets, $sim);

        $auditId = DB::table('simulation_audits')->insertGetId([
            'question' => (string) $payload['question'],
            'detected_status' => $statusLabel,
            'requested_value' => $requestedValue,
            'applied_value' => $appliedValue,
            'was_clamped' => $hasClamped,
            'provider' => $provider,
            'simulator_payload' => json_encode([
                'targets' => $targets,
                'simulator_result' => $sim,
            ], JSON_UNESCAPED_UNICODE),
            'system_summary' => $systemSummary,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $targetLines = array_map(
            fn ($x) => "- {$x['status']}: {$x['value']}%",
            $targets
        );

        $prompt = implode("\n", [
            'Anda analis proses kredit enterprise.',
            '',
            'Data hasil simulasi:',
            'Target efisiensi:',
            ...$targetLines,
            'Baseline SLA: '.(string) ($sim['baseline_sla'] ?? 0),
            'Projected SLA: '.(string) ($sim['projected_sla'] ?? 0),
            'Avg TAT sebelum: '.(string) ($sim['avg_tat_before'] ?? 0),
            'Avg TAT sesudah: '.(string) ($sim['avg_tat_after'] ?? 0),
            'Total penghematan hari: '.(string) ($sim['total_saving_days'] ?? 0),
            '',
            'Tugas:',
            'Buat ringkasan insight maksimal 4 kalimat.',
            '- Gunakan minimal 2 angka dari data.',
            '- Fokus pada interpretasi dampak.',
            '- Jangan mengubah angka.',
            '- Jangan menghitung ulang.',
            '- Bahasa Indonesia profesional.',
        ]);

        try {
            $aiSummary = $this->runAiByProvider(
                provider: $provider,
                prompt: $prompt,
                systemPrompt: 'Anda analis proses kredit enterprise. Gunakan angka dari data apa adanya.',
                model: $provider === 'gemini'
                    ? config('services.gemini_ai.chat_model')
                    : config('services.cloudflare_ai.chat_model'),
                maxTokens: 220,
                temperature: 0.2,
                useDecisionToken: false
            );
        } catch (\Throwable $e) {
            DB::table('simulation_audits')
                ->where('id', $auditId)
                ->update([
                    'error_message' => $e->getMessage(),
                    'updated_at' => now(),
                ]);
            throw $e;
        }

        DB::table('simulation_audits')
            ->where('id', $auditId)
            ->update([
                'ai_summary' => $aiSummary,
                'updated_at' => now(),
            ]);

        return response()->json([
            'system_summary' => $systemSummary,
            'ai_summary' => $aiSummary,
            'simulator_result' => $sim,
            'audit_id' => $auditId,
        ]);
    }

    public function playbook(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'provider' => ['nullable', 'in:cloudflare,gemini'],
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
            'loan_summary' => ['nullable', 'array'],
            'loan_summary.total_applications' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.avg_apps_per_month' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.total_approved_limit' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.avg_limit_per_app' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.average_tat' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.mode_tat' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.q1' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.median' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.q3' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.iqr' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.outlier_boundary' => ['nullable', 'numeric', 'min:0'],
            'loan_summary.total_outliers' => ['nullable', 'numeric', 'min:0'],
            'bottleneck_by_status' => ['nullable', 'array', 'max:3'],
            'bottleneck_by_status.*.status' => ['required_with:bottleneck_by_status', 'string', 'max:120'],
            'bottleneck_by_status.*.rows' => ['required_with:bottleneck_by_status', 'array', 'max:10'],
            'bottleneck_by_status.*.rows.*.loan_size' => ['required_with:bottleneck_by_status', 'string', 'max:24'],
            'bottleneck_by_status.*.rows.*.total_applications' => ['nullable', 'integer', 'min:0'],
            'bottleneck_by_status.*.rows.*.avg_loop' => ['nullable', 'numeric', 'min:0'],
            'bottleneck_by_status.*.rows.*.avg_tat' => ['nullable', 'numeric', 'min:0'],
            'bottleneck_by_status.*.rows.*.avg_status_tat' => ['nullable', 'numeric', 'min:0'],
            'bottleneck_by_status.*.rows.*.sla_breach_count' => ['nullable', 'integer', 'min:0'],
            'bottleneck_by_status.*.rows.*.sla_breach_pct' => ['nullable', 'numeric', 'min:0'],
        ]);
        $provider = $this->resolveProvider($payload['provider'] ?? null);

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
            '- gunakan juga loan_summary dan bottleneck_by_status (loan size, loop, avg tat, avg status tat, SLA breach)',
            '',
            'Total app: '.((int) ($payload['total_app'] ?? 0)),
            'Loan summary: '.json_encode($payload['loan_summary'] ?? [], JSON_UNESCAPED_UNICODE),
            'Status profiles (top 3): '.json_encode($statusProfiles, JSON_UNESCAPED_UNICODE),
            'Bottleneck detail by status: '.json_encode($payload['bottleneck_by_status'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        $text = $this->runAiByProvider(
            provider: $provider,
            prompt: $prompt,
            systemPrompt: 'Anda analis proses kredit. Berikan rekomendasi action yang ringkas dan realistis.',
            model: $provider === 'gemini'
                ? config('services.gemini_ai.playbook_model')
                : config('services.cloudflare_ai.playbook_model'),
            maxTokens: 320,
            temperature: 0.2,
            useDecisionToken: true
        );

        $actions = $this->enrichMissingActionsPerStatus(
            actions: $this->parsePlaybookActions($text),
            statusProfiles: $statusProfiles,
            loanSummary: (array) ($payload['loan_summary'] ?? []),
            bottleneckByStatus: (array) ($payload['bottleneck_by_status'] ?? []),
            provider: $provider
        );

        $alignedActions = $this->alignActionsToTopStatuses($actions, $statusProfiles);

        return response()->json([
            'actions' => $alignedActions,
            'raw' => $text,
            'provider' => $provider,
        ]);
    }

    private function resolveProvider(?string $provider): string
    {
        $p = strtolower(trim((string) $provider));
        return in_array($p, ['cloudflare', 'gemini'], true) ? $p : 'cloudflare';
    }

    private function runAiByProvider(
        string $provider,
        string $prompt,
        string $systemPrompt,
        ?string $model,
        int $maxTokens,
        float $temperature,
        bool $useDecisionToken
    ): string {
        if ($provider === 'gemini') {
            return $this->runGeminiAi($prompt, $systemPrompt, $model, $maxTokens, $temperature);
        }

        return $this->runCloudflareAi($prompt, $systemPrompt, $model, $maxTokens, $temperature, $useDecisionToken);
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

    private function runGeminiAi(
        string $prompt,
        string $systemPrompt,
        ?string $model,
        int $maxTokens,
        float $temperature
    ): string {
        $apiKey = trim((string) config('services.gemini_ai.api_key'));
        $modelName = trim((string) $model);
        if ($apiKey === '' || $modelName === '') {
            throw new HttpResponseException(response()->json([
                'message' => 'Gemini AI configuration missing. Please check .env values.',
            ], 503));
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";
        $userText = $systemPrompt."\n\n".$prompt;

        $response = Http::timeout(30)
            ->acceptJson()
            ->post($url, [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $userText,
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);

        if (! $response->successful()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Gemini AI request failed.',
                'status' => $response->status(),
                'error' => $response->json() ?? $response->body(),
            ], $response->status()));
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (! is_string($text) || trim($text) === '') {
            throw new HttpResponseException(response()->json([
                'message' => 'Gemini AI returned empty response.',
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
            $decoded = $this->decodeJsonLoose($jsonText);
            if (!is_array($decoded)) {
                $decoded = $this->extractPartialJsonObjects($text);
            }
        }

        if (is_array($decoded) && count($decoded) === 0 && str_contains($text, '{')) {
            // Fallback tambahan: model kadang kirim array rusak tapi object di tengah masih valid.
            $decoded = $this->extractPartialJsonObjects($text);
        }
        if (is_array($decoded) && count($decoded) === 0) {
            // Fallback terakhir: ekstrak pair key-value dari teks parsial meski JSON rusak.
            $decoded = $this->extractActionsByPattern($text);
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

    private function enrichMissingActionsPerStatus(array $actions, array $statusProfiles, array $loanSummary, array $bottleneckByStatus, string $provider): array
    {
        $normalize = fn (string $s) => preg_replace('/[^a-z0-9]/', '', strtolower($s));
        $seedByKey = [];
        foreach ($actions as $a) {
            $k = $normalize((string) ($a['status'] ?? ''));
            if ($k !== '' && !isset($seedByKey[$k])) {
                $seedByKey[$k] = $a;
            }
        }

        $bottleneckMap = [];
        foreach ($bottleneckByStatus as $row) {
            $status = (string) ($row['status'] ?? '');
            $k = $normalize($status);
            if ($k !== '') {
                $bottleneckMap[$k] = $row;
            }
        }

        $built = [];
        foreach ($statusProfiles as $profile) {
            $status = (string) ($profile['status'] ?? '');
            $k = $normalize($status);
            if ($k === '') {
                continue;
            }

            $seed = $seedByKey[$k] ?? null;
            $single = null;
            $singlePrompt = implode("\n", [
                'Analisis 1 status prioritas untuk menurunkan SLA breach/TAT.',
                'Balas HANYA JSON object valid (tanpa markdown).',
                'Format:',
                '{"status":"...","action":"...","summary":"...","sla_target_impact":"...","reason":"..."}',
                'Batasan:',
                '- action max 6 kata',
                '- summary max 20 kata',
                '- sla_target_impact max 10 kata',
                '- reason max 16 kata',
                '- gunakan minimal 2 angka dari data status',
                '',
                'Loan summary: '.json_encode($loanSummary, JSON_UNESCAPED_UNICODE),
                'Status profile: '.json_encode($profile, JSON_UNESCAPED_UNICODE),
                'Bottleneck status detail: '.json_encode($bottleneckMap[$k] ?? [], JSON_UNESCAPED_UNICODE),
            ]);

            for ($attempt = 0; $attempt < 2; $attempt++) {
                try {
                    $singleText = $this->runAiByProvider(
                        provider: $provider,
                        prompt: $singlePrompt,
                        systemPrompt: 'Anda analis proses kredit. Berikan rekomendasi action yang ringkas dan realistis.',
                        model: $provider === 'gemini'
                            ? config('services.gemini_ai.playbook_model')
                            : config('services.cloudflare_ai.playbook_model'),
                        maxTokens: 200,
                        temperature: 0.2,
                        useDecisionToken: true
                    );
                    $parsed = $this->parseSinglePlaybookAction($singleText, $status);
                    if (!empty($parsed['action'])) {
                        $single = $parsed;
                        break;
                    }
                } catch (\Throwable $e) {
                    // Retry next attempt
                }
            }

            if ($single && !empty($single['action'])) {
                $built[] = [
                    'status' => $status,
                    'action' => trim((string) ($single['action'] ?? '')),
                    'summary' => trim((string) ($single['summary'] ?? '')),
                    'sla_target_impact' => trim((string) ($single['sla_target_impact'] ?? '')),
                    'reason' => trim((string) ($single['reason'] ?? '')),
                    'source' => 'ai',
                ];
                continue;
            }

            if ($seed && !empty($seed['action'])) {
                $built[] = [
                    'status' => $status,
                    'action' => trim((string) ($seed['action'] ?? '')),
                    'summary' => trim((string) ($seed['summary'] ?? '')),
                    'sla_target_impact' => trim((string) ($seed['sla_target_impact'] ?? '')),
                    'reason' => trim((string) ($seed['reason'] ?? '')),
                    'source' => 'ai',
                ];
                continue;
            }

            $fallback = $this->buildHeuristicActionFromProfile($profile);
            $built[] = [
                'status' => $status,
                'action' => trim((string) ($fallback['action'] ?? 'monitor mingguan')),
                'summary' => trim((string) ($fallback['summary'] ?? '')),
                'sla_target_impact' => trim((string) ($fallback['sla_target_impact'] ?? '')),
                'reason' => trim((string) ($fallback['reason'] ?? '')),
                'source' => 'fallback',
            ];
        }

        return array_slice($built, 0, 3);
    }

    private function alignActionsToTopStatuses(array $actions, array $statusProfiles): array
    {
        $normalize = fn (string $s) => preg_replace('/[^a-z0-9]/', '', strtolower($s));

        $byKey = [];
        foreach ($actions as $idx => $a) {
            $k = $normalize((string) ($a['status'] ?? ''));
            if ($k !== '' && !isset($byKey[$k])) {
                $byKey[$k] = $a;
            }
        }

        $aligned = [];
        foreach ($statusProfiles as $idx => $profile) {
            $status = (string) ($profile['status'] ?? '');
            $k = $normalize($status);
            $picked = null;
            $source = 'ai';

            if ($k !== '' && isset($byKey[$k])) {
                $picked = $byKey[$k];
            } else {
                // Fuzzy match if AI status slightly differs.
                foreach ($byKey as $candidateKey => $candidate) {
                    if ($candidateKey !== '' && (str_contains($candidateKey, $k) || str_contains($k, $candidateKey))) {
                        $picked = $candidate;
                        break;
                    }
                }
            }

            // Fallback by index if no match.
            if (!$picked && isset($actions[$idx]) && is_array($actions[$idx])) {
                $picked = $actions[$idx];
            }

            // Deterministic final fallback so top-3 always filled.
            if (!$picked) {
                $picked = $this->buildHeuristicActionFromProfile($profile);
                $source = 'fallback';
            }
            if ($picked && isset($picked['source'])) {
                $source = trim((string) $picked['source']) !== '' ? trim((string) $picked['source']) : $source;
            }

            $action = trim((string) ($picked['action'] ?? ''));
            if ($action === '') {
                $picked = $this->buildHeuristicActionFromProfile($profile);
                $source = 'fallback';
                $action = trim((string) ($picked['action'] ?? 'monitor mingguan'));
            }

            $aligned[] = [
                'status' => $status,
                'action' => $action !== '' ? $action : 'monitor mingguan',
                'summary' => trim((string) ($picked['summary'] ?? '')),
                'sla_target_impact' => trim((string) ($picked['sla_target_impact'] ?? '')),
                'reason' => trim((string) ($picked['reason'] ?? '')),
                'source' => $source,
            ];
        }

        return array_slice($aligned, 0, 3);
    }

    private function buildHeuristicActionFromProfile(array $profile): array
    {
        $impact = (float) ($profile['impact_pct'] ?? 0);
        $avgTat = (float) ($profile['avg_tat_days'] ?? 0);
        $avgStep = (float) ($profile['avg_step'] ?? 0);
        $outlier = (int) ($profile['outlier_count'] ?? 0);

        $action = 'monitor mingguan';
        if ($avgStep >= 1.5) {
            $action = 'pangkas loop approval';
        } elseif ($outlier >= 8) {
            $action = 'tutup outlier prioritas';
        } elseif ($impact >= 15 || $avgTat >= 250) {
            $action = 'percepat SLA internal';
        }

        return [
            'action' => $action,
            'summary' => "Impact {$impact}% | Avg TAT {$avgTat} | Avg Step {$avgStep}",
            'sla_target_impact' => 'SLA breach -8% 2 bulan',
            'reason' => "Pemicu utama: tat {$avgTat}, step {$avgStep}, outlier {$outlier}",
        ];
    }

    private function parseSinglePlaybookAction(string $text, string $fallbackStatus): array
    {
        if (preg_match('/\{[^{}]*\}/', $text, $m)) {
            $candidate = $m[0];
            $item = $this->decodeJsonLoose($candidate);
            if (is_array($item)) {
                return [
                    'status' => trim((string) ($item['status'] ?? $fallbackStatus)),
                    'action' => trim((string) ($item['action'] ?? '')),
                    'summary' => trim((string) ($item['summary'] ?? '')),
                    'sla_target_impact' => trim((string) ($item['sla_target_impact'] ?? '')),
                    'reason' => trim((string) ($item['reason'] ?? '')),
                ];
            }
        }

        return [
            'status' => $fallbackStatus,
            'action' => '',
            'summary' => '',
            'sla_target_impact' => '',
            'reason' => '',
        ];
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
            $item = $this->decodeJsonLoose($obj);
            if (is_array($item)) {
                $rows[] = $item;
            }
        }

        return $rows;
    }

    private function extractActionsByPattern(string $text): array
    {
        $clean = str_replace(['\\"', '\\/'], ['"', '/'], $text);
        $clean = stripslashes($clean);

        $pattern = '/"status"\s*:\s*"([^"]+)".*?"action"\s*:\s*"([^"]+)".*?"summary"\s*:\s*"([^"]+)".*?"sla_target_impact"\s*:\s*"([^"]+)".*?"reason"\s*:\s*"([^"]+)"/si';
        preg_match_all($pattern, $clean, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return [];
        }

        $rows = [];
        foreach ($matches as $m) {
            $rows[] = [
                'status' => trim((string) ($m[1] ?? '')),
                'action' => trim((string) ($m[2] ?? '')),
                'summary' => trim((string) ($m[3] ?? '')),
                'sla_target_impact' => trim((string) ($m[4] ?? '')),
                'reason' => trim((string) ($m[5] ?? '')),
            ];
        }

        return $rows;
    }

    private function decodeJsonLoose(string $json): mixed
    {
        $candidates = [
            $json,
            str_replace(['\\"', '\\/'], ['"', '/'], $json),
            stripslashes($json),
        ];

        foreach ($candidates as $candidate) {
            try {
                return json_decode($candidate, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                // try next candidate
            }
        }

        return null;
    }

    private function looksTruncated(string $text): bool
    {
        $t = trim($text);
        if ($t === '') {
            return false;
        }

        if (preg_match('/[\\.!\\?…]$/u', $t)) {
            return false;
        }

        return preg_match('/\\b(dengan|dan|atau|yang|untuk|karena|sehingga|maka|adalah|yaitu|bahwa|seperti)\\s*$/iu', $t) === 1
            || mb_strlen($t) > 240;
    }
}
