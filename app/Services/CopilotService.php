<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\Projects;
use App\Models\TestCase;
use App\Models\TestReport;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CopilotService
{
    private ?User $user;

    public function __construct(?User $user = null)
    {
        $this->user = $user ?: Auth::user();
    }

    /**
     * Build compact context for the LLM turn to optimize token usage.
     */
    public function buildContext(?int $projectId, ?int $reportId, array $currentDrafts = []): string
    {
        $lines = ["=== KONTEKS SISTEM QA AKTIF ==="];

        // 1. Project Context
        if ($projectId) {
            $project = Projects::find($projectId);
            if ($project) {
                $lines[] = "Project Aktif: {$project->name} [Kode: {$project->project_code}] (Versi: {$project->version})";
            }
        } else {
            $lines[] = "Project: (Belum dipilih / Umum)";
        }

        // 2. Report Context
        $testType = 'functional';
        if ($reportId) {
            $report = TestReport::withCount('testCases')->find($reportId);
            if ($report) {
                $testType = $report->test_type;
                $lines[] = "Laporan Aktif: \"{$report->report_name}\" [Tipe: {$report->test_type_label}, Sprint: " . ($report->sprint ?: '-') . ", Siklus: {$report->cycle}]";
                
                // Existing TC summary (compact top 8)
                $existingTCs = TestCase::where('report_id', $report->id)
                    ->select('test_case_id', 'title', 'status', 'bug_ticket')
                    ->take(10)
                    ->get();

                if ($existingTCs->isNotEmpty()) {
                    $tcSummaries = [];
                    foreach ($existingTCs as $tc) {
                        $tcSummaries[] = "{$tc->test_case_id}: {$tc->title} [Status: {$tc->status}" . ($tc->bug_ticket ? ", Bug: {$tc->bug_ticket}" : '') . "]";
                    }
                    $lines[] = "Test Cases Tersimpan di Report (" . count($existingTCs) . " contoh): " . implode(' | ', $tcSummaries);
                }

                // Active Defects Context
                $qg = $report->getQualityGateMetrics();
                $lines[] = "Status Quality Gate Report: {$qg['verdict']} (Pass Rate: {$qg['pass_rate']}%, Selesai: {$qg['progress']}%, Defect Kritis Aktif: {$qg['active_critical_bugs']})";
            }
        } else {
            $lines[] = "Laporan: (Draft baru / Belum disimpan ke report)";
        }

        // 3. Current Live Draft in Right Panel
        if (!empty($currentDrafts)) {
            $draftCount = count($currentDrafts);
            $lines[] = "=== DRAFT TEST CASES DI PANEL KANAN SAAT INI ({$draftCount} items) ===";
            foreach (array_slice($currentDrafts, 0, 10) as $d) {
                $tcId = $d['testCaseId'] ?? $d['test_case_id'] ?? 'TC-?';
                $title = $d['testTitle'] ?? $d['title'] ?? 'Test Case';
                $st = $d['status'] ?? 'Not Run';
                $lines[] = "- [{$tcId}] {$title} (Status: {$st})";
            }
            if ($draftCount > 10) {
                $lines[] = "... dan " . ($draftCount - 10) . " test case draft lainnya.";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Build the specialized Master System Prompt for Copilot.
     */
    public function getSystemPrompt(string $contextString, string $testType = 'functional'): string
    {
        return <<<PROMPT
Kamu adalah **Antigravity QA Copilot** — asisten AI ahli pengujian perangkat lunak dan perangkat keras (Software & Hardware QA Lead) yang bertugas berpasangan dengan tester dalam format interaktif Studio 3-Kolom.

{$contextString}

PRINSIP UTAMA:
1. "Chat sebagai input & analisis, bukan penyimpan data akhir."
2. Jawabanmu harus selalu terdiri dari DUA BAGIAN TERPISAH menggunakan tag pembatas:
   - `[COPILOT_RESPONSE]`: Percakapan natural, saran teknis, penjelasan ringkas, atau jawaban analisa untuk tester yang ditampilkan di jendela chat.
   - `[DRAFT_PAYLOAD]`: Objek JSON valid yang berisi aksi dan array draft test cases terstruktur untuk dimasukkan LIVE ke panel Draft di sisi kanan.

KEMAMPUAN YANG HARUS KAMU DUKUNG:
1. GENERATE DARI REQUIREMENT:
   - Jika user meminta dibuatkan test case dari requirement / user story, hasilkan skenario lengkap dengan kombinasi Positive, Negative, dan Edge cases.
   - Buat langkah terurut (minimal 2-4 langkah), expected result konkret, dan status default "Not Run".
2. PARSE CATATAN MENTAH / LOG / CHAT:
   - Jika user mem-paste catatan kasar, obrolan WhatsApp, dump log error, atau checklist, pecah menjadi test cases terstruktur, tentukan status aktual (Pass/Fail), dan isi actual result.
3. UPDATE CEPAT:
   - Jika user mengabarkan hasil uji (contoh: "TC-003 fail, tombol checkout tidak respon"), update status TC tersebut di draft menjadi "Fail", isi actualResult, dan picu saran pencatatan bug.
4. TANYA DATA & EVALUASI QUALITY GATE:
   - Jika user bertanya (misal: "Apakah report ini sudah layak GO?", "Bug apa yang aktif?"), jelaskan kondisi Quality Gate berdasarkan konteks di atas.
5. STANDUP HARIAN:
   - Jika user meminta dibuatkan standup harian, susun format Pekerjaan Selesai, Kendala, dan Rencana Selanjutnya.
6. REGRESSION TEST:
   - Salin dan kembangkan skenario pengujian regresi untuk fitur terdampak.

ATURAN WAJIB QA-TPL-001 (STANDAR MASTER V0.2):
- Aturan 2: Status hanya boleh satu dari: "Pass", "Fail", "Blocked", "Not Run", "N/A".
- Aturan 3: Jika status "Fail", wajib disarankan Bug ID (kosongkan bugTicket jika belum ada tiket, agar sistem menampilkan peringatan inline).
- Aturan 6: Precondition adalah syarat awal, Steps adalah urutan aksi, Test Data adalah data input konkret.
- Aturan 7 (Hardware): Jika tipe Hardware, parameter tanpa Batas Lulus / Spec kuantitatif tidak boleh "Pass" (gunakan "Blocked" dengan catatan "Spec belum tersedia").

FORMAT OUTPUT WAJIB:
Kamu HARUS selalu menyertakan format berikut tanpa markdown pembungkus di luar tag:

[COPILOT_RESPONSE]
Penjelasan teks percakapan ramah dan profesional di sini...
[/COPILOT_RESPONSE]

[DRAFT_PAYLOAD]
{
  "action": "generate", 
  "message_summary": "Ringkasan tindakan (misal: Menghasilkan 4 test case fitur login)",
  "test_cases": [
    {
      "testCaseId": "TC-001",
      "testTitle": "Judul skenario pengujian",
      "caseType": "Positive",
      "priority": "High",
      "moduleFeature": "Nama modul utama",
      "feature": "Nama sub-fitur",
      "refRequirement": "REQ-01",
      "componentSubsystem": "BMS / RF (jika hardware)",
      "parameterMeasured": "Tegangan / RSSI (jika hardware)",
      "unit": "V / dBm (jika hardware)",
      "passLimitSpec": "Batas lulus kuantitatif (jika hardware)",
      "specSource": "Datasheet (jika hardware)",
      "measuredValue": "Nilai terukur aktual (jika hardware)",
      "precondition": "Kondisi awal sebelum pengujian",
      "steps": [
        {"step": "Langkah 1...", "expected": "Hasil 1..."},
        {"step": "Langkah 2...", "expected": "Hasil 2..."}
      ],
      "actualResult": "Hasil pengamatan di lapangan",
      "status": "Not Run",
      "bugTicket": "",
      "evidenceUrl": "",
      "notesQA": ""
    }
  ],
  "quick_bug_trigger": null,
  "standup_text": null
}
[/DRAFT_PAYLOAD]

CATATAN:
- Jika user HANYA bertanya data biasa (misal: "Berapa pass rate saat ini?"), pada `[DRAFT_PAYLOAD]` isi `"action": "query_reply"`, dan `"test_cases": []`.
- Selalu pastikan JSON di dalam `[DRAFT_PAYLOAD]` valid dan tertutup rapat.
PROMPT;
    }

    /**
     * Execute chat turn with automatic provider fallback:
     * Gemini -> OpenRouter -> OmniRoute -> Ollama
     *
     * @return array [ 'response_text' => string, 'payload' => array, 'provider_used' => string ]
     */
    public function ask(string $userMessage, ?int $projectId = null, ?int $reportId = null, array $currentDrafts = [], string $preferredProvider = 'gemini'): array
    {
        $contextString = $this->buildContext($projectId, $reportId, $currentDrafts);
        $testType = 'functional';
        if ($reportId) {
            $rep = TestReport::find($reportId);
            if ($rep) $testType = $rep->test_type;
        }

        $systemPrompt = $this->getSystemPrompt($contextString, $testType);

        // Fallback chain
        $providers = array_unique(array_filter([
            $preferredProvider,
            'openai',
            'gemini',
            'openrouter',
            'omniroute',
            'ollama',
        ]));

        $lastException = null;
        $rawOutput = '';
        $providerUsed = '';

        foreach ($providers as $prov) {
            try {
                $rawOutput = $this->callProvider($prov, $userMessage, $systemPrompt);
                if (!empty(trim($rawOutput))) {
                    $providerUsed = $prov;
                    break;
                }
            } catch (\Throwable $e) {
                Log::warning("[CopilotService] Provider '{$prov}' failed: " . $e->getMessage() . " -> Trying fallback...");
                $lastException = $e;
            }
        }

        if (empty($rawOutput)) {
            throw new Exception("Semua provider AI gagal memproses pesan. Penyebab terakhir: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
        }

        return $this->parseCopilotResponse($rawOutput, $providerUsed, $currentDrafts);
    }

    /**
     * Dispatch to provider API call.
     */
    private function callProvider(string $provider, string $userMessage, string $systemPrompt): string
    {
        $user = $this->user ?: Auth::user();

        return match ($provider) {
            'openai'     => $this->callOpenAi($userMessage, $systemPrompt, $user),
            'gemini'     => $this->callGemini($userMessage, $systemPrompt, $user),
            'openrouter' => $this->callOpenRouter($userMessage, $systemPrompt, $user),
            'omniroute'  => $this->callOmniRoute($userMessage, $systemPrompt, $user),
            'ollama'     => $this->callOllama($userMessage, $systemPrompt, $user),
            default      => throw new Exception("Provider '{$provider}' tidak dikenal."),
        };
    }

    // ──────────────────────────────────────────────────────────
    //  Provider Clients
    // ──────────────────────────────────────────────────────────

    private function callGemini(string $userMessage, string $systemPrompt, ?User $user): string
    {
        $apiKey = $user ? $user->getResolvedGeminiKey() : config('ai.gemini.api_key');
        if (empty($apiKey)) {
            throw new Exception("Gemini API Key belum disetel.");
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        $response = Http::timeout(45)->post($url, [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $userMessage]]],
            ],
            'generationConfig' => [
                'temperature'     => 0.3,
                'maxOutputTokens' => 4096,
            ],
        ]);

        if (!$response->successful()) {
            throw new Exception("Gemini Error: " . ($response->json('error.message') ?? $response->body()));
        }

        return $response->json('candidates.0.content.parts.0.text') ?? '';
    }

    private function callOpenRouter(string $userMessage, string $systemPrompt, ?User $user): string
    {
        $apiKey = $user ? $user->getResolvedOpenRouterKey() : config('ai.openrouter.api_key');
        if (empty($apiKey)) {
            throw new Exception("OpenRouter API Key belum disetel.");
        }

        $model = $user ? $user->getResolvedOpenRouterModel() : config('ai.openrouter.model', 'meta-llama/llama-3.1-8b-instruct:free');

        $response = Http::timeout(45)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => 'https://qaportal.local',
            'X-Title'       => 'QA Portal Copilot',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => 0.3,
        ]);

        if (!$response->successful()) {
            throw new Exception("OpenRouter Error: " . ($response->json('error.message') ?? $response->body()));
        }

        return $response->json('choices.0.message.content') ?? '';
    }

    private function callOmniRoute(string $userMessage, string $systemPrompt, ?User $user): string
    {
        $apiKey  = $user ? $user->getResolvedOmnirouteKey() : config('ai.omniroute.api_key');
        $baseUrl = rtrim($user ? $user->getResolvedOmnirouteBaseUrl() : config('ai.omniroute.base_url', 'http://202.155.143.157:20128/v1'), '/');
        $model   = $user ? $user->getResolvedOmnirouteModel() : config('ai.omniroute.model', 'meta-llama/llama-3.1-8b-instruct');

        $url = "{$baseUrl}/chat/completions";

        $response = Http::timeout(45)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->post($url, [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => 0.3,
        ]);

        if (!$response->successful()) {
            throw new Exception("OmniRoute Error: " . $response->body());
        }

        return $response->json('choices.0.message.content') ?? '';
    }

    private function callOllama(string $userMessage, string $systemPrompt, ?User $user): string
    {
        $baseUrl = rtrim($user ? $user->getResolvedOllamaBaseUrl() : config('ai.ollama.base_url', 'http://localhost:11434'), '/');
        $model   = $user ? $user->getResolvedOllamaModel() : config('ai.ollama.model', 'llama3');

        $url = "{$baseUrl}/api/chat";

        $response = Http::timeout(60)->post($url, [
            'model'    => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'stream'   => false,
        ]);

        if (!$response->successful()) {
            throw new Exception("Ollama Error: " . $response->body());
        }

        return $response->json('message.content') ?? '';
    }

    private function callOpenAi(string $userMessage, string $systemPrompt, ?User $user): string
    {
        $apiKey  = $user ? $user->getResolvedOpenAiKey() : config('ai.openai.api_key');
        $baseUrl = rtrim($user ? $user->getResolvedOpenAiBaseUrl() : config('ai.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model   = $user ? $user->getResolvedOpenAiModel() : config('ai.openai.model', 'gpt-4o-mini');

        if (empty($apiKey)) {
            throw new Exception("OpenAI / Celeris API Key belum disetel. Silakan atur di Settings > Setup AI & API Keys.");
        }

        $url = $baseUrl;
        if (!str_contains($url, '/chat/completions')) {
            $url .= '/chat/completions';
        }

        $response = Http::timeout(60)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->post($url, [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
            'temperature' => 0.2,
            'max_tokens'  => 4096,
        ]);

        if (!$response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body();
            throw new Exception("OpenAI/Celeris Error ({$response->status()}): {$errorMsg}");
        }

        $text = $response->json('choices.0.message.content')
             ?? $response->json('choices.0.delta.content')
             ?? $response->json('output')
             ?? $response->json('text')
             ?? '';

        if (empty($text) && str_contains($response->body(), 'data:')) {
            $lines = explode("\n", $response->body());
            $collected = '';
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, 'data:') && !str_contains($trimmed, '[DONE]')) {
                    $jsonStr = trim(substr($trimmed, 5));
                    $data = json_decode($jsonStr, true);
                    $collected .= $data['choices'][0]['delta']['content']
                               ?? $data['choices'][0]['message']['content']
                               ?? '';
                }
            }
            $text = $collected;
        }

        return $text;
    }

    // ──────────────────────────────────────────────────────────
    //  Response & JSON Parsing / Repair
    // ──────────────────────────────────────────────────────────

    public function parseCopilotResponse(string $rawOutput, string $providerUsed, array $currentDrafts = []): array
    {
        $chatText = '';
        $draftPayload = [
            'action'          => 'query_reply',
            'message_summary' => '',
            'test_cases'      => [],
        ];

        // 1. Extract [COPILOT_RESPONSE]
        if (preg_match('/\[COPILOT_RESPONSE\]([\s\S]*?)\[\/COPILOT_RESPONSE\]/i', $rawOutput, $m)) {
            $chatText = trim($m[1]);
        } else {
            // If tag missing, strip any [DRAFT_PAYLOAD] tag from chat text
            $chatText = trim(preg_replace('/\[DRAFT_PAYLOAD\][\s\S]*?\[\/DRAFT_PAYLOAD\]/i', '', $rawOutput));
            $chatText = trim(preg_replace('/```json[\s\S]*?```/i', '', $chatText));
        }

        // 2. Extract [DRAFT_PAYLOAD]
        $rawJson = '';
        if (preg_match('/\[DRAFT_PAYLOAD\]([\s\S]*?)\[\/DRAFT_PAYLOAD\]/i', $rawOutput, $m)) {
            $rawJson = trim($m[1]);
        } elseif (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/i', $rawOutput, $m)) {
            $rawJson = trim($m[1]);
        }

        if (!empty($rawJson)) {
            $decoded = json_decode($rawJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Run JSON repair
                $repaired = $this->repairJson($rawJson);
                $decoded  = json_decode($repaired, true);
            }

            if (is_array($decoded)) {
                $draftPayload = array_merge($draftPayload, $decoded);
            }
        }

        // Normalize test cases in draft payload
        $normalizedTCs = [];
        if (!empty($draftPayload['test_cases']) && is_array($draftPayload['test_cases'])) {
            foreach ($draftPayload['test_cases'] as $idx => $tc) {
                if (!is_array($tc)) continue;
                $normalizedTCs[] = $this->normalizeTestCaseItem($tc, $idx);
            }
            $draftPayload['test_cases'] = $normalizedTCs;
        }

        // Handle UPDATE action if user requested an update on existing drafts
        if (($draftPayload['action'] ?? '') === 'update' && !empty($normalizedTCs)) {
            $mergedDrafts = $currentDrafts;
            foreach ($normalizedTCs as $newTc) {
                $matched = false;
                foreach ($mergedDrafts as &$oldTc) {
                    if (!empty($newTc['testCaseId']) && ($oldTc['testCaseId'] ?? $oldTc['test_case_id'] ?? '') === $newTc['testCaseId']) {
                        $oldTc = array_merge($oldTc, $newTc);
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    $mergedDrafts[] = $newTc;
                }
            }
            $draftPayload['test_cases'] = $mergedDrafts;
        }

        return [
            'response_text' => $chatText ?: 'Hasil analisis telah selesai diproses.',
            'payload'       => $draftPayload,
            'provider_used' => $providerUsed,
        ];
    }

    private function normalizeTestCaseItem(array $tc, int $index): array
    {
        $rawStatus = $tc['status'] ?? 'Not Run';
        $status = match(strtoupper(str_replace([' ', '_', '/'], '', $rawStatus))) {
            'PASS'           => 'Pass',
            'FAIL', 'FAILED' => 'Fail',
            'BLOCKED'        => 'Blocked',
            'NOTRUN'         => 'Not Run',
            'NA'             => 'N/A',
            default          => in_array($rawStatus, ['Not Run', 'Pass', 'Fail', 'Blocked', 'N/A']) ? $rawStatus : 'Not Run',
        };

        // Steps array normalization
        $steps = [];
        if (!empty($tc['steps']) && is_array($tc['steps'])) {
            foreach ($tc['steps'] as $s) {
                if (is_array($s)) {
                    $steps[] = [
                        'step'     => $s['step'] ?? '',
                        'expected' => $s['expected'] ?? '',
                    ];
                } elseif (is_string($s)) {
                    $steps[] = ['step' => $s, 'expected' => ''];
                }
            }
        }
        if (empty($steps)) {
            $steps = [['step' => 'Lakukan pengujian sesuai judul skenario', 'expected' => 'Sistem berfungsi sesuai spesifikasi']];
        }

        $tcId = $tc['testCaseId'] ?? $tc['test_case_id'] ?? ('TC-' . sprintf('%03d', $index + 1));
        $bugTicket = $tc['bugTicket'] ?? $tc['bug_ticket'] ?? '';

        // Rule 3 Check: Fail without Bug ID
        $warningRule3 = ($status === 'Fail' && empty(trim((string)$bugTicket)));

        // Rule 7 Check: HW Pass without Spec
        $passLimitSpec = trim((string)($tc['passLimitSpec'] ?? $tc['pass_limit_spec'] ?? ''));
        $warningRule7 = ($status === 'Pass' && (empty($passLimitSpec) || strtoupper($passLimitSpec) === 'TBD'));

        return [
            'selected'           => true, // checked by default in draft panel
            'testCaseId'         => $tcId,
            'testTitle'          => $tc['testTitle'] ?? $tc['title'] ?? ('Test Case ' . ($index + 1)),
            'caseType'           => $tc['caseType'] ?? $tc['case_type'] ?? 'Positive',
            'priority'           => $tc['priority'] ?? 'Medium',
            'moduleFeature'      => $tc['moduleFeature'] ?? $tc['module_feature'] ?? 'General',
            'feature'            => $tc['feature'] ?? '',
            'refRequirement'     => $tc['refRequirement'] ?? $tc['ref_requirement'] ?? '',
            'componentSubsystem' => $tc['componentSubsystem'] ?? $tc['component_subsystem'] ?? '',
            'parameterMeasured'  => $tc['parameterMeasured'] ?? $tc['parameter_measured'] ?? '',
            'unit'               => $tc['unit'] ?? '',
            'passLimitSpec'      => $passLimitSpec,
            'specSource'         => $tc['specSource'] ?? $tc['spec_source'] ?? '',
            'measuredValue'      => $tc['measuredValue'] ?? $tc['measured_value'] ?? '',
            'precondition'       => $tc['precondition'] ?? '',
            'steps'              => $steps,
            'actualResult'       => $tc['actualResult'] ?? $tc['actual_result'] ?? '',
            'status'             => $status,
            'bugTicket'          => $bugTicket,
            'evidenceUrl'        => $tc['evidenceUrl'] ?? $tc['evidence_url'] ?? '',
            'notesQA'            => $tc['notesQA'] ?? $tc['notes_qa'] ?? '',
            'warning_rule_3'     => $warningRule3,
            'warning_rule_7'     => $warningRule7,
        ];
    }

    private function repairJson(string $json): string
    {
        $json = preg_replace('/```(?:json)?/i', '', $json);
        $json = preg_replace('/,(\s*[\}\]])/', '$1', $json);
        $json = preg_replace('/^\s*\/\/.*/m', '', $json);
        $json = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $json);

        // Balance unclosed quotes and braces
        $firstBrace = strpos($json, '{');
        if ($firstBrace === false) return '{}';
        $json = substr($json, $firstBrace);

        $stack = [];
        $inString = false;
        $escape = false;
        $length = strlen($json);

        for ($i = 0; $i < $length; $i++) {
            $char = $json[$i];
            if ($inString) {
                if ($escape) $escape = false;
                elseif ($char === '\\') $escape = true;
                elseif ($char === '"') $inString = false;
            } else {
                if ($char === '"') $inString = true;
                elseif ($char === '{' || $char === '[') array_push($stack, $char);
                elseif ($char === '}' || $char === ']') {
                    if (!empty($stack)) {
                        $top = end($stack);
                        if (($char === '}' && $top === '{') || ($char === ']' && $top === '[')) {
                            array_pop($stack);
                        }
                    }
                }
            }
        }

        if ($inString) $json .= '"';

        $json = preg_replace('/,\s*$/', '', $json);

        while (!empty($stack)) {
            $open = array_pop($stack);
            $json .= ($open === '{') ? '}' : ']';
        }

        return $json;
    }
}
