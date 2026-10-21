<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private string $provider;
    private array  $config;

    /**
     * Build dynamic system prompt template for QA report parsing based on test type.
     */
    public function getSystemPrompt(string $testType = 'functional'): string
    {
        $testType = strtolower(trim($testType));
        $validTypes = ['functional', 'integration', 'regression', 'performance', 'reliability', 'stress', 'negative', 'validation', 'hardware'];
        if (!in_array($testType, $validTypes)) {
            $testType = 'functional';
        }

        $typeSpecificFields = match ($testType) {
            'functional' => <<<FIELDS
  "moduleFeature": "string — nama modul utama (misal: Auth, Dashboard, Telemetry)",
  "feature": "string — sub-fitur spesifik yang diuji (misal: Login Form, Filter Tanggal)",
  "refRequirement": "string — ID requirement / PRD / User Story (misal: REQ-01, US-12, atau TBD)",
  "testData": "string — data pengujian konkret yang digunakan (misal: user: admin@test.id, pass: P@ss123)"
FIELDS,
            'integration' => <<<FIELDS
  "integrationPoint": "string — sistem/endpoint/modul yang terintegrasi (misal: Order Service <-> Payment Gateway)",
  "dependencies": "string — dependensi / kondisi parameter service (misal: Payment Gateway service aktif)"
FIELDS,
            'regression' => <<<FIELDS
  "relatedFeature": "string — fitur terkait yang mungkin terdampak (misal: Payment Processing)",
  "reasonLinked": "string — alasan pengujian / ID bug / linked test case (misal: Bug fix pada flow pembayaran)"
FIELDS,
            'performance' => <<<FIELDS
  "testTypePerf": "Load Test|Stress Test|Spike Test|Endurance Test",
  "testParams": "string — parameter pengujian performa (misal: Concurrent users: 100, Duration: 5 minutes)"
FIELDS,
            'reliability' => <<<FIELDS
  "testTypeRel": "Failure Rate|Mean Time Between Failures|Recovery Time",
  "durationFailure": "string — durasi atau tingkat kegagalan (misal: 2% failure rate over 24 hours)"
FIELDS,
            'stress' => <<<FIELDS
  "testTypeStress": "Load Stress|Soak Stress|Spike Stress",
  "stressParams": "string — parameter stress test (misal: Max users: 500, Ramp-up: 10 min)"
FIELDS,
            'negative' => <<<FIELDS
  "moduleFeatureNeg": "string — modul/fitur negative test (misal: Payment Gateway)",
  "testDataNeg": "string — data pengujian negatif/invalid (misal: Invalid card data, expired token)"
FIELDS,
            'validation' => <<<FIELDS
  "validationType": "Input Validation|Business Rule Validation|Boundary Value Analysis|Form Constraint|Authentication & Security Rule",
  "validationCriteria": "string — kriteria/aturan validasi yang diverifikasi (misal: Email harus sesuai format RFC 5322, password minimal 8 karakter)"
FIELDS,
            'hardware' => <<<FIELDS
  "componentSubsystem": "string — komponen/subsystem hardware (misal: BMS / Charger, RF / Telemetry, Gimbal / Camera)",
  "parameterMeasured": "string — parameter kuantitatif yang diukur (misal: Waktu charging 20% -> 80%, RSSI / Link Quality, Suhu permukaan casing)",
  "unit": "string — satuan ukur (misal: menit, dBm / %, °C, m, V / mV)",
  "passLimitSpec": "string — batas lulus / spec kuantitatif (misal: <= 45 menit, >= -85 dBm, <= 55°C, atau TBD)",
  "specSource": "string — sumber spesifikasi (misal: Datasheet, Kontrak/TOR, Regulasi, Internal Spec)",
  "measuredValue": "string — nilai terukur aktual di lapangan (misal: 38 menit, -72 dBm, 48°C)",
  "deviceTested": "string — nama/model perangkat atau SN unit yang diuji (misal: Alfa AWUS036ACH B902, SN-001)",
  "environmentSpec": "string — kondisi lingkungan uji: suhu ambien, indoor/outdoor, jarak, frekuensi (misal: Outdoor 31°C, Frekuensi 2.4 GHz, Jarak ~26m)"
FIELDS,
        };

        return <<<PROMPT
Kamu adalah Lead QA Engineer & Test Automation Specialist expert yang bertugas menganalisis data testing mentah (UNSTRUCTURED RAW DATA) dari tester/user dan menghasilkan laporan QA terstruktur yang siap pakai untuk form web QA Portal.

PRINSIP UTAMA:
- INPUT DARI USER BISA BERUPA TEKS BEBAS APAPUN:
  1. Chat obrolan santai/informal WhatsApp, Slack, Discord, Telegram (misal: "tadi nyoba checkout barang habis eh malah tembus...")
  2. Tiket bug / issue report (Jira, Linear, GitHub Issues, bug title & desc)
  3. Log error console / dump stack trace / response JSON / payload cURL API
  4. Catatan harian tester, checklist manual kasar, bullet point acak, atau teks campur Indonesia-Inggris
  5. User story, acceptance criteria, atau notulen meeting pengujian
- USER BEBAS MENGETIK / MEM-PASTE APA SAJA: Kamu bertugas mengekstrak, menganalisis, menyaring, dan merekonstruksi informasi tersebut agar sesuai 100% dengan struktur form yang ada.

INSTRUKSI ANALISIS & REKONSTRUKSI:
1. INFORMASI REPORT:
   - "report_name": Judul laporan QA yang profesional, ringkas, dan relevan (contoh: "Sprint 26 - Feature Checkout & Payment Gateway Testing").
   - "suggested_project_name": Nama aplikasi, sistem, atau modul utama yang disebutkan di teks (misal: "E-Commerce", "Core Banking", "POS Mobile"). Jika tidak ada, isi string kosong "".
   - "report_date": Format YYYY-MM-DD. Ambil tanggal yang disebutkan, jika tidak ada gunakan tanggal hari ini.
   - "description": Ringkasan tujuan, ruang lingkup, dan konteks pengujian.

2. PEMECAHAN TEST CASE (MULTI-SCENARIO) — PENTING & WAJIB:
   - JIKA DATA MENTAH MEMUAT BEBERAPA MODUL / FITUR / SKENARIO / LIST BUG (misalnya: Dashboard - Self Drive, Using Driver, Inspection; Vehicle Archive; Asset Management; User Management; History; Settings & Logs), KAMU WAJIB MEMECAHNYA MENJADI MULTIPLE TEST CASES DALAM ARRAY "test_cases" (1 ITEM TEST CASE TERPISAH UNTUK SETIAP MODUL/FITUR/KENDALA).
   - DILARANG MENGGABUNGKAN SELURUH CATATAN DARI BANYAK MODUL MENJADI HANYA 1 TEST CASE TUNGGAL!
   - Contoh: Jika ada 6-10 modul/topik di catatan mentah user, kamu WAJIB menghasilkan minimal 6-12 item terpisah di array "test_cases".

3. REKONSTRUKSI LANGKAH PENGUJIAN (STEPS):
   - WALAUPUN DATA MENTAH TIDAK MENULISKAN LANGKAH-LANGKAH SECARA DETAIL, kamu WAJIB merekonstruksi minimal 2 hingga 4 langkah sequential yang logis (Contoh: 1. Buka halaman ... -> 2. Lakukan aksi ... -> 3. Verifikasi hasil ...).
   - Setiap langkah harus memiliki "step" (tindakan tester) dan "expected" (hasil yang diharapkan).

4. EVALUASI HASIL & STATUS:
   - "status":
     * "Pass" jika pengujian berhasil / sesuai ekspektasi.
     * "Fail" jika terjadi error, bug, validasi jebol, crash, atau tidak sesuai harapan.
     * "Blocked" jika pengujian terhenti karena blocker / server down / dependensi tidak siap.
     * "Not Run" jika belum dieksekusi.
     * "N/A" jika tidak berlaku pada rilis ini.
   - "actual_result": Fakta aktual yang terjadi di lapangan (khususnya untuk bug/failure, sebutkan perilaku atau pesan errornya).
   - "notes_qa": Catatan atau insight analisa dari sudut pandang QA.
   - "notes_programmer": Catatan teknis atau saran perbaikan untuk programmer/developer jika relevan.
   - "test_case_id": ID kode test case jika ada (contoh: "SPC-LOGIN-001" atau "TC-01").
   - "buckleup_ticket": Nomor tiket eksekusi Buckleup jika ada (contoh: "NEWM2-255").
   - "bug_ticket": Nomor tiket bug Buckleup jika ada (contoh: "NEWM2-251") terutama untuk status Fail.
   - "evidence_url": URL screenshot, Google Drive, Loom, atau bukti lain jika ditemukan di teks mentah (jika tidak ada, isi "").

5. FIELD SPESIFIK TIPE TESTING:
   - Isi field spesifik {$testType} berdasarkan konteks teks mentah atau buat inferensi logis yang paling tepat.

FORMAT OUTPUT (SANGAT PENTING):
- DILARANG keras menuliskan teks narasi intro/outro, kalimat pembuka, atau nalar analisis seperti "Let me analyze...", "So I need to break this...", "TC1: ...".
- RESPONSMU HARUS 100% BERUPA OBJECT JSON MURNI YANG DIAWALI LANGSUNG DENGAN KARAKTER '{' DAN KUNCI "report_name", SERTA DIAKHIRI DENGAN '}'.

{
  "report_name": "string",
  "suggested_project_name": "string",
  "test_type": "{$testType}",
  "report_date": "YYYY-MM-DD",
  "description": "string",
  "test_cases": [
    {
      "testTitle": "string — judul test case spesifik",
      "test_case_id": "string — misal TC-SW-001 atau TC-HW-001",
      "caseType": "Positive|Negative|Edge",
      "priority": "High|Medium|Low|Safety-Critical (HW)",
      "buckleup_ticket": "string — misal NEWM2-255",
      "bug_ticket": "string — misal NEWM2-251",
      "precondition": "string — syarat awal sebelum pengujian",
{$typeSpecificFields},
      "status": "Pass|Fail|Blocked|Not Run|N/A",
      "actual_result": "string",
      "notes_qa": "string",
      "notes_programmer": "string",
      "evidence_url": "string",
      "steps": [
        {
          "step": "string — tindakan pengujian",
          "expected": "string — ekspektasi hasil"
        }
      ]
    }
  ]
}
PROMPT;
    }

    /**
     * Build system prompt for Daily Standup generation.
     */
    public function getDailyStandupPrompt(string $userName, string $date): string
    {
        return <<<PROMPT
Kamu adalah Asisten AI Profesional untuk Daily Standup / Laporan Aktivitas Harian Tim QA & Engineering.

TUGAS:
Analisis data aktivitas pengujian QA dari sistem dan/atau catatan manual dari user untuk tanggal {$date}, lalu susun ke dalam format Laporan Daily Standup terstruktur.

ATURAN STRUKTUR JSON:
Kembalikan HANYA format JSON valid tanpa markdown code fence (```json), tanpa teks intro atau outro, dengan struktur:

{
  "tanggal": "{$date}",
  "nama": "{$userName}",
  "pekerjaan_selesai": "string — rincian pekerjaan yang selesai. Jika ada lebih dari satu, tuliskan padat dan jelas dipisahkan koma atau poin ringkas (misal: 'Ikut Pengiriman wifi phantom, screen record untuk kebutuhan video shoot wifi phantom, Testing modul Checkout & Payment Gateway (2 PASS, 1 FAILED)')",
  "kendala": "string — kendala / blocker yang dihadapi. Jika tidak ada kendala atau user menyatakan aman, tulis 'ga ada' atau 'Tidak ada kendala'",
  "rencana_berikutnya": "string — rencana pekerjaan selanjutnya atau esok hari (misal: 'Transmate testing, verifikasi perbaikan bug voucher promo')"
}

PANDUAN KONTEN:
1. "pekerjaan_selesai":
   - Integrasikan kegiatan dari sistem QA (jika ada) dan catatan manual user.
   - Pertahankan istilah asli dari catatan user (misal: 'wifi phantom', 'video shoot', dsb.).
2. "kendala":
   - Jika ada bug/failure dari QA atau kendala teknis dari user, sebutkan di sini.
   - Jika tidak ada kendala, isi 'ga ada'.
3. "rencana_berikutnya":
   - Ambil dari catatan user jika ada, atau buatkan kelanjutan yang logis jika user belum menyebutkannya.
PROMPT;
    }

    public function __construct(string $provider = '')
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $defaultProvider = $user?->getResolvedDefaultAiProvider() ?: config('ai.default_provider', 'gemini');
        $this->provider = $provider ?: $defaultProvider;

        // Base config from config/ai.php
        $baseConfig = config("ai.{$this->provider}", []);

        // Override with user's saved settings if available
        if ($user) {
            if ($this->provider === 'gemini') {
                if (empty($baseConfig['api_key'])) {
                    $baseConfig['api_key'] = $user->getResolvedGeminiKey();
                }
            } elseif ($this->provider === 'openrouter') {
                if (empty($baseConfig['api_key'])) {
                    $baseConfig['api_key'] = $user->getResolvedOpenRouterKey();
                }
                if (empty($baseConfig['model'])) {
                    $baseConfig['model'] = $user->getResolvedOpenRouterModel();
                }
            } elseif ($this->provider === 'ollama') {
                if (empty($baseConfig['base_url'])) {
                    $baseConfig['base_url'] = $user->getResolvedOllamaBaseUrl();
                }
                if (empty($baseConfig['model'])) {
                    $baseConfig['model'] = $user->getResolvedOllamaModel();
                }
            } elseif ($this->provider === 'omniroute') {
                if (empty($baseConfig['api_key'])) {
                    $baseConfig['api_key'] = $user->getResolvedOmnirouteKey();
                }
                if (empty($baseConfig['base_url'])) {
                    $baseConfig['base_url'] = $user->getResolvedOmnirouteBaseUrl();
                }
                if (empty($baseConfig['model'])) {
                    $baseConfig['model'] = $user->getResolvedOmnirouteModel();
                }
            } elseif ($this->provider === 'openai') {
                if (empty($baseConfig['api_key'])) {
                    $baseConfig['api_key'] = $user->getResolvedOpenAiKey();
                }
                if (empty($baseConfig['base_url'])) {
                    $baseConfig['base_url'] = $user->getResolvedOpenAiBaseUrl();
                }
                if (empty($baseConfig['model'])) {
                    $baseConfig['model'] = $user->getResolvedOpenAiModel();
                }
            }
        }

        $this->config = $baseConfig;
    }

    /**
     * Parse raw testing notes into structured QA report JSON.
     *
     * @param  string $rawText
     * @param  string $testType
     * @return array  Parsed report data
     * @throws \Exception
     */
    public function parse(string $rawText, string $testType = 'functional'): array
    {
        $systemPrompt = $this->getSystemPrompt($testType);

        $json = match ($this->provider) {
            'gemini'      => $this->callGemini($rawText, $systemPrompt),
            'openrouter'  => $this->callOpenRouter($rawText, $systemPrompt),
            'ollama'      => $this->callOllama($rawText, $systemPrompt),
            'omniroute'   => $this->callOmniRoute($rawText, $systemPrompt),
            'openai'      => $this->callOpenAi($rawText, $systemPrompt),
            default       => throw new \Exception("Provider '{$this->provider}' tidak didukung."),
        };
        
        return $this->validateAndDecode($json, $testType);
    }

    /**
     * Generate structured Daily Standup from QA system activities and/or manual user notes.
     *
     * @param  string $userName
     * @param  string $date
     * @param  array  $systemActivities
     * @param  string $manualNotes
     * @return array  Daily standup data
     * @throws \Exception
     */
    public function generateDailyStandup(string $userName, string $date, array $systemActivities = [], string $manualNotes = ''): array
    {
        $systemPrompt = $this->getDailyStandupPrompt($userName, $date);

        $contextParts = [];
        if (!empty($systemActivities['reports']) || !empty($systemActivities['test_cases'])) {
            $contextParts[] = "=== AKTIVITAS PENGUJIAN QA DARI SISTEM (Tanggal: {$date}) ===";
            if (!empty($systemActivities['reports'])) {
                $contextParts[] = "Daftar Laporan QA yang Dikerjakan:";
                foreach ($systemActivities['reports'] as $rep) {
                    $contextParts[] = "- {$rep['name']} (Project: {$rep['project']}, Tipe: {$rep['test_type']}, Total Cases: {$rep['total_cases']}, PASS: {$rep['pass']}, FAILED: {$rep['fail']})";
                }
            }
            if (!empty($systemActivities['test_cases'])) {
                $contextParts[] = "Detail Test Cases:";
                foreach ($systemActivities['test_cases'] as $tc) {
                    $item = "- [{$tc['status']}] {$tc['title']}";
                    if (!empty($tc['actual_result']) && $tc['status'] !== 'PASS') {
                        $item .= " — Temuan: {$tc['actual_result']}";
                    }
                    $contextParts[] = $item;
                }
            }
        }

        if (!empty(trim($manualNotes))) {
            $contextParts[] = "=== CATATAN MANUAL DARI USER ===";
            $contextParts[] = trim($manualNotes);
        }

        if (empty($contextParts)) {
            $contextParts[] = "(Catatan: User tidak menyertakan detail khusus. Buat ringkasan template standar).";
        }

        $rawText = implode("\n\n", $contextParts);

        $jsonString = match ($this->provider) {
            'gemini'     => $this->callGemini($rawText, $systemPrompt),
            'openrouter' => $this->callOpenRouter($rawText, $systemPrompt),
            'ollama'     => $this->callOllama($rawText, $systemPrompt),
            'omniroute'  => $this->callOmniRoute($rawText, $systemPrompt),
            'openai'     => $this->callOpenAi($rawText, $systemPrompt),
            default      => throw new \Exception("Provider '{$this->provider}' tidak didukung."),
        };

        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            // Fallback parsing if LLM returned text instead of JSON
            $completedTasks = '';
            $blockers = 'ga ada';
            $nextPlans = '';

            if (preg_match('/PEKERJAAN SELESAI:\s*(.+?)(?=\n\s*KENDALA:|$)/si', $jsonString, $m)) {
                $completedTasks = trim($m[1]);
            }
            if (preg_match('/KENDALA:\s*(.+?)(?=\n\s*RENCANA BERIKUTNYA:|$)/si', $jsonString, $m)) {
                $blockers = trim($m[1]);
            }
            if (preg_match('/RENCANA BERIKUTNYA:\s*(.+?)$/si', $jsonString, $m)) {
                $nextPlans = trim($m[1]);
            }

            $data = [
                'tanggal'            => $date,
                'nama'               => $userName,
                'pekerjaan_selesai'  => $completedTasks ?: $manualNotes,
                'kendala'            => $blockers,
                'rencana_berikutnya' => $nextPlans ?: '-',
            ];
        }

        $tanggal          = !empty($data['tanggal']) ? $data['tanggal'] : $date;
        $nama             = !empty($data['nama']) ? $data['nama'] : $userName;
        $pekerjaanSelesai = !empty($data['pekerjaan_selesai']) ? trim($data['pekerjaan_selesai']) : '-';
        $kendala          = !empty($data['kendala']) ? trim($data['kendala']) : 'ga ada';
        $rencanaBerikut   = !empty($data['rencana_berikutnya']) ? trim($data['rencana_berikutnya']) : '-';

        $formattedOutput = "TANGGAL: {$tanggal}\n" .
                           "NAMA: {$nama}\n" .
                           "PEKERJAAN SELESAI: {$pekerjaanSelesai}\n\n" .
                           "KENDALA: {$kendala}\n" .
                           "RENCANA BERIKUTNYA: {$rencanaBerikut}";

        return [
            'tanggal'            => $tanggal,
            'nama'               => $nama,
            'pekerjaan_selesai'  => $pekerjaanSelesai,
            'kendala'            => $kendala,
            'rencana_berikutnya' => $rencanaBerikut,
            'formatted_output'   => $formattedOutput,
        ];
    }

    // ──────────────────────────────────────────────────────
    //  Provider: Google Gemini
    // ──────────────────────────────────────────────────────

    private function callGemini(string $rawText, string $systemPrompt): string
    {
        $apiKey = $this->config['api_key'] ?? '';

        if (empty($apiKey)) {
            throw new \Exception('Gemini API Key belum dikonfigurasi. Silakan masukkan Gemini API Key di Settings atau di modal AI.');
        }

        $fullPrompt = $systemPrompt . "\n\nData mentah dari tester:\n" . $rawText;
        $userModel  = $this->config['model'] ?? 'gemini-1.5-flash';

        // ── Strategy 1: Try new Interactions API (POST /v1beta/interactions) ──
        $interactionModels = array_values(array_unique(array_filter([
            $userModel,
            'gemini-1.5-flash',
            'gemini-2.0-flash',
        ])));

        foreach ($interactionModels as $model) {
            $cleanModelName = str_replace('models/', '', $model);
            try {
                $response = Http::timeout(35)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type'   => 'application/json',
                    ])
                    ->post("https://generativelanguage.googleapis.com/v1beta/interactions", [
                        'model' => $cleanModelName,
                        'input' => $fullPrompt,
                    ]);

                if ($response->successful()) {
                    $jsonData = $response->json();
                    
                    $text = $jsonData['output'] 
                         ?? $jsonData['outputs'][0]['text'] 
                         ?? $jsonData['text'] 
                         ?? $jsonData['candidates'][0]['content']['parts'][0]['text'] 
                         ?? '';

                    if (is_array($text)) {
                        $text = json_encode($text);
                    }

                    if (!empty($text)) {
                        Log::info("[AiService] Gemini Interactions API success with model {$cleanModelName}");
                        return $this->cleanJsonString($text);
                    }
                }
            } catch (\Exception $e) {
                Log::debug("[AiService] Interactions API attempt failed for {$cleanModelName}: " . $e->getMessage());
            }
        }

        // ── Strategy 2: Fallback to generateContent API ──
        $modelPath = $this->resolveGeminiModel($apiKey);
        $url       = "https://generativelanguage.googleapis.com/v1beta/{$modelPath}:generateContent?key={$apiKey}";

        $body = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => "Data mentah dari tester:\n" . $rawText],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature'      => 0.2,
                'topP'             => 0.95,
                'maxOutputTokens'  => 4096,
                'responseMimeType' => 'application/json',
            ],
        ];

        try {
            $response = Http::timeout(35)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $body);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text') ?? '';
                if (!empty($text)) {
                    Log::info("[AiService] Gemini generateContent success with model {$modelPath}");
                    return $this->cleanJsonString($text);
                }
            }

            $errorMsg = $response->json('error.message') ?? $response->body();
            throw new \Exception("Gemini API Error: " . $errorMsg);

        } catch (\Exception $e) {
            Log::error("[AiService] Gemini generateContent exception with model {$modelPath}: " . $e->getMessage());
            throw $e;
        }
    }

    private function resolveGeminiModel(string $apiKey): string
    {
        $userModel = $this->config['model'] ?? '';

        try {
            $response = Http::timeout(10)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? $response->body();
                throw new \Exception("Gemini API Key Error: " . $errorMsg);
            }

            $models = $response->json('models') ?? [];

            $available = collect($models)
                ->filter(function ($m) {
                    $methods = $m['supportedGenerationMethods'] ?? [];
                    return in_array('generateContent', $methods);
                })
                ->pluck('name')
                ->values();

            if ($available->isEmpty()) {
                throw new \Exception("Tidak ada model Gemini yang mendukung 'generateContent' untuk API Key ini.");
            }

            if ($userModel) {
                $formattedUserModel = str_starts_with($userModel, 'models/') ? $userModel : "models/{$userModel}";
                if ($available->contains($formattedUserModel)) {
                    return $formattedUserModel;
                }
            }

            $preferred = [
                'models/gemini-2.0-flash',
                'models/gemini-1.5-flash',
                'models/gemini-1.5-flash-latest',
                'models/gemini-1.5-pro',
                'models/gemini-pro',
            ];

            foreach ($preferred as $pref) {
                if ($available->contains($pref)) {
                    return $pref;
                }
            }

            $flash = $available->first(fn($name) => str_contains($name, 'flash'));
            return $flash ?: $available->first();

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Gemini API Key Error')) {
                throw $e;
            }
            return $userModel ? (str_starts_with($userModel, 'models/') ? $userModel : "models/{$userModel}") : 'models/gemini-1.5-flash';
        }
    }

    // ──────────────────────────────────────────────────────
    //  Provider: OpenRouter (OpenAI-compatible)
    // ──────────────────────────────────────────────────────

    private function callOpenRouter(string $rawText, string $systemPrompt): string
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');

        $apiKey = $this->config['api_key'] ?? '';

        if (empty($apiKey)) {
            throw new \Exception('OpenRouter API key belum dikonfigurasi. Silakan isi di menu Settings > Setup AI & API Keys.');
        }

        $url = ($this->config['base_url'] ?? 'https://openrouter.ai/api/v1') . '/chat/completions';
        $preferredModel = $this->config['model'] ?? 'meta-llama/llama-3.1-8b-instruct:free';

        $candidateModels = array_unique([
            $preferredModel,
            'google/gemini-2.0-flash-exp:free',
            'openrouter/auto',
        ]);

        $lastError = '';

        foreach ($candidateModels as $model) {
            try {
                $body = [
                    'model'    => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => "Data mentah dari tester:\n" . $rawText],
                    ],
                    'temperature' => 0.2,
                    'max_tokens'  => 4096,
                ];

                $response = Http::timeout(45)
                    ->withHeaders([
                        'Authorization' => "Bearer {$apiKey}",
                        'Content-Type'  => 'application/json',
                        'HTTP-Referer'  => config('app.url', 'http://localhost'),
                        'X-Title'       => config('app.name', 'QA Portal') . ' QA Portal',
                    ])
                    ->post($url, $body);

                if ($response->successful()) {
                    $text = $response->json('choices.0.message.content')
                         ?? $response->json('choices.0.message.reasoning')
                         ?? $response->json('choices.0.message.reasoning_content')
                         ?? $response->json('choices.0.message.reasoning_details.0.text')
                         ?? '';

                    if (!empty($text)) {
                        return $this->cleanJsonString($text);
                    }
                }

                $error = $response->json('error.message') ?? $response->body();
                $lastError = $error;
                Log::warning("[AiService::callOpenRouter] Model '{$model}' failed: {$error}. Retrying next model...");

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("[AiService::callOpenRouter] Exception on model '{$model}': {$lastError}");
            }
        }

        throw new \Exception("OpenRouter API error: {$lastError}");
    }

    // ──────────────────────────────────────────────────────
    //  Provider: Ollama (local)
    // ──────────────────────────────────────────────────────

    private function callOllama(string $rawText, string $systemPrompt): string
    {
        $baseUrl = rtrim($this->config['base_url'] ?? 'http://localhost:11434', '/');
        $model   = $this->config['model'] ?? 'llama3.2';

        $prompt = $systemPrompt . "\n\nData mentah dari tester:\n" . $rawText
            . "\n\nPENTING: Kembalikan HANYA JSON murni tanpa penjelasan apapun.";

        $body = [
            'model'  => $model,
            'prompt' => $prompt,
            'stream' => false,
            'format' => 'json',
            'options' => [
                'temperature' => 0.2,
                'num_predict' => 4096,
            ],
        ];

        $response = Http::timeout(120)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$baseUrl}/api/generate", $body);

        if ($response->failed()) {
            $status = $response->status();
            if ($status === 0 || $response->clientError()) {
                throw new \Exception("Tidak bisa terhubung ke Ollama di {$baseUrl}. Pastikan Ollama telah berjalan.");
            }
            throw new \Exception("Ollama error: " . $response->body());
        }

        $text = $response->json('response') ?? '';

        if (empty($text)) {
            throw new \Exception('Ollama tidak mengembalikan response yang valid.');
        }

        return $this->cleanJsonString($text);
    }

    // ──────────────────────────────────────────────────────
    //  Provider: OmniRoute (http://202.155.143.157:20128)
    // ──────────────────────────────────────────────────────

    private function callOmniRoute(string $rawText, string $systemPrompt): string
    {
        $url = $this->config['base_url'] ?? 'http://202.155.143.157:20128/v1/chat/completions';
        if (!str_contains($url, '/chat/completions')) {
            $url = rtrim($url, '/') . '/chat/completions';
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];
        if (!empty($this->config['api_key'])) {
            $headers['Authorization'] = "Bearer " . $this->config['api_key'];
        }

        $preferredModel  = $this->config['model'] ?? 'auto';
        $candidateModels = array_unique([$preferredModel, 'auto/coding', 'auto', 'auto/best-coding']);

        $lastException = null;

        foreach ($candidateModels as $model) {
            try {
                $body = [
                    'model'    => $model,
                    'stream'   => false,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => "Data mentah dari tester:\n" . $rawText],
                    ],
                    'temperature' => 0.2,
                    'max_tokens'  => 4096,
                ];

                $response = Http::timeout(60)
                    ->withHeaders($headers)
                    ->post($url, $body);

                if ($response->failed()) {
                    continue;
                }

                $text = $response->json('choices.0.message.content')
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

                $cleaned = $this->cleanJsonString($text);
                if (strlen($cleaned) > 20 && str_starts_with($cleaned, '{') && str_ends_with($cleaned, '}')) {
                    return $cleaned;
                }
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        throw new \Exception($lastException ? $lastException->getMessage() : 'OmniRoute server tidak mengembalikan response JSON yang valid.');
    }

    // ──────────────────────────────────────────────────────
    //  Provider: OpenAI / Celeris / Custom Compatible
    // ──────────────────────────────────────────────────────

    private function callOpenAi(string $rawText, string $systemPrompt): string
    {
        $apiKey  = $this->config['api_key'] ?? '';
        $baseUrl = $this->config['base_url'] ?? 'https://api.openai.com/v1';
        $model   = $this->config['model'] ?? 'gpt-4o-mini';

        if (empty($apiKey)) {
            throw new \Exception('API Key belum dikonfigurasi. Silakan masukkan API Key OpenAI/Celeris di menu Settings atau di form AI.');
        }

        $url = rtrim($baseUrl, '/');
        if (!str_contains($url, '/chat/completions')) {
            $url .= '/chat/completions';
        }

        $headers = [
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ];

        $body = [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => "Data mentah dari tester:\n" . $rawText],
            ],
            'temperature' => 0.2,
            'max_tokens'  => 4096,
        ];

        $response = Http::timeout(60)
            ->withHeaders($headers)
            ->post($url, $body);

        if ($response->failed()) {
            $errMsg = $response->json('error.message') ?? $response->body();
            throw new \Exception("OpenAI/Celeris Error ({$response->status()}): {$errMsg}");
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

        if (empty($text)) {
            throw new \Exception('Response kosong dari endpoint OpenAI/Celeris.');
        }

        return $this->cleanJsonString($text);
    }

    // ──────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────

    private function cleanJsonString(string $text): string
    {
        // 1. Strip markdown codeblock backticks if present
        $text = preg_replace('/```(?:json)?/i', '', $text);

        // 2. Locate the start of the JSON object (prefer {"report_name" or first {)
        $firstBrace = false;
        if (preg_match('/\{\s*"report_name"/i', $text, $m, PREG_OFFSET_CAPTURE)) {
            $firstBrace = $m[0][1];
        } else {
            $firstBrace = strpos($text, '{');
        }

        if ($firstBrace !== false) {
            $lastBrace = strrpos($text, '}');
            if ($lastBrace !== false && $lastBrace > $firstBrace) {
                $text = substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
            } else {
                $text = substr($text, $firstBrace);
            }
        }

        return trim($text);
    }

    private function repairJsonString(string $json): string
    {
        // Remove trailing commas before closing braces/brackets e.g. ", }" or ", ]"
        $json = preg_replace('/,(\s*[\}\]])/', '$1', $json);

        // Remove JavaScript-style single line comments "// ..."
        $json = preg_replace('/^\s*\/\/.*/m', '', $json);

        // Fix invalid control characters in JSON string values (keep \n, \r, \t)
        $json = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $json);

        return trim($json);
    }

    private function repairTruncatedJson(string $json): string
    {
        $json = trim($json);
        if (empty($json)) return '{}';

        $firstBrace = strpos($json, '{');
        if ($firstBrace !== false) {
            $json = substr($json, $firstBrace);
        } else {
            return '{}';
        }

        // Pass 1: Handle unclosed string literals
        $inString = false;
        $escape = false;
        $length = strlen($json);

        for ($i = 0; $i < $length; $i++) {
            $char = $json[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($char === '\\') {
                    $escape = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
            } else {
                if ($char === '"') {
                    $inString = true;
                }
            }
        }

        if ($inString) {
            $json .= '"';
        }

        // Pass 2: Clean trailing dangles (commas, colons, incomplete keys)
        $json = preg_replace('/,\s*$/', '', $json);
        $json = preg_replace('/:\s*$/', ': ""', $json);
        $json = preg_replace('/,\s*("[^"]*")?\s*:?\s*$/', '', $json);

        // Pass 3: Balance stack of open braces '{' and '['
        $stack = [];
        $inString = false;
        $escape = false;
        $length = strlen($json);

        for ($i = 0; $i < $length; $i++) {
            $char = $json[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($char === '\\') {
                    $escape = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
            } else {
                if ($char === '"') {
                    $inString = true;
                } elseif ($char === '{' || $char === '[') {
                    array_push($stack, $char);
                } elseif ($char === '}' || $char === ']') {
                    if (!empty($stack)) {
                        $top = end($stack);
                        if (($char === '}' && $top === '{') || ($char === ']' && $top === '[')) {
                            array_pop($stack);
                        }
                    }
                }
            }
        }

        if ($inString) {
            $json .= '"';
        }

        $json = preg_replace('/,\s*$/', '', $json);

        while (!empty($stack)) {
            $open = array_pop($stack);
            if ($open === '{') {
                $json .= '}';
            } elseif ($open === '[') {
                $json .= ']';
            }
        }

        return $json;
    }

    private function validateAndDecode(string $json, string $testType = 'functional'): array
    {
        $rawOriginal = $json;
        $json = $this->cleanJsonString($json);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $repaired = $this->repairJsonString($json);
            $data = json_decode($repaired, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Attempt repair: fix unescaped newlines inside string values
            $repaired = preg_replace_callback('/"([^"\\\\]*|\\\\.)*"/', function ($m) {
                return str_replace(["\r\n", "\n", "\r", "\t"], ["\\n", "\\n", "\\n", "\\t"], $m[0]);
            }, $repaired ?? $json);
            $data = json_decode($repaired, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Attempt repair: auto-close truncated brackets/quotes
            $repaired = $this->repairTruncatedJson($repaired ?? $json);
            $data = json_decode($repaired, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::warning('[AiService] JSON decode failed, running fallback raw text parser', [
                'json_error' => json_last_error_msg(),
                'original'   => substr($rawOriginal, 0, 500)
            ]);

            $data = $this->buildFallbackFromRawText($rawOriginal, $testType);
        }

        $data['report_name']            = !empty($data['report_name']) ? $data['report_name'] : 'QA Test Report';
        $data['suggested_project_name'] = $data['suggested_project_name'] ?? '';
        $data['test_type']              = $testType;
        $data['report_date']            = !empty($data['report_date']) ? $data['report_date'] : now()->format('Y-m-d');
        $data['description']            = $data['description'] ?? '';
        $data['test_cases']             = is_array($data['test_cases'] ?? null) ? $data['test_cases'] : [];

        if (empty($data['test_cases'])) {
            $data['test_cases'][] = [
                'testTitle'    => 'Hasil Pengujian QA',
                'precondition' => 'Data mentah telah dianalisis',
                'status'       => 'PASS',
                'testResult'   => 'PASSED',
                'actualResult' => 'Pengujian berhasil dianalisis dari data mentah',
                'notesQA'      => '',
                'steps'        => [['step' => 'Lakukan pengujian sesuai deskripsi', 'expected' => 'Sistem berfungsi sesuai ekspektasi']]
            ];
        }

        $validTypes = ['functional', 'integration', 'regression', 'performance', 'reliability', 'stress', 'negative', 'validation', 'hardware'];
        if (!in_array($data['test_type'], $validTypes)) {
            $data['test_type'] = 'functional';
        }

        foreach ($data['test_cases'] as &$tc) {
            if (!is_array($tc)) {
                $tc = ['testTitle' => is_string($tc) ? $tc : 'Test Case'];
            }
            $tc['testTitle']        = $tc['testTitle'] ?? $tc['test_title'] ?? 'Test Case';
            $tc['test_case_id']     = $tc['test_case_id'] ?? $tc['testCaseId'] ?? '';
            $tc['testCaseId']       = $tc['test_case_id'];
            $tc['buckleup_ticket']  = $tc['buckleup_ticket'] ?? $tc['buckleupTicket'] ?? '';
            $tc['buckleupTicket']   = $tc['buckleup_ticket'];
            $tc['bug_ticket']       = $tc['bug_ticket'] ?? $tc['bugTicket'] ?? '';
            $tc['bugTicket']        = $tc['bug_ticket'];
            $tc['precondition']     = $tc['precondition'] ?? '';
            $statusRaw              = strtoupper(str_replace([' ', '_', '/'], '', (string)($tc['status'] ?? 'Pass')));
            $cleanStatus            = match($statusRaw) {
                'PASS'           => 'Pass',
                'FAIL', 'FAILED' => 'Fail',
                'BLOCKED'        => 'Blocked',
                'NOTRUN'         => 'Not Run',
                'NA'             => 'N/A',
                default          => in_array($tc['status'] ?? '', ['Not Run', 'Pass', 'Fail', 'Blocked', 'N/A']) ? $tc['status'] : 'Pass',
            };
            $tc['status']           = $cleanStatus;
            $tc['testResult']       = $cleanStatus === 'Pass' ? 'PASS' : ($cleanStatus === 'Fail' ? 'FAIL' : $cleanStatus);
            $tc['actual_result']    = $tc['actual_result'] ?? $tc['actualResult'] ?? '';
            $tc['actualResult']     = $tc['actual_result'];
            $tc['notes_qa']         = $tc['notes_qa'] ?? $tc['notesQA'] ?? '';
            $tc['notesQA']          = $tc['notes_qa'];
            $tc['notes_programmer'] = $tc['notes_programmer'] ?? $tc['notesProgrammer'] ?? '';
            $tc['notesProgrammer']  = $tc['notes_programmer'];
            $tc['evidence_url']     = $tc['evidence_url'] ?? $tc['evidenceUrl'] ?? '';
            $tc['evidenceUrl']      = $tc['evidence_url'];

            if (empty($tc['steps']) || !is_array($tc['steps'])) {
                $tc['steps'] = [['step' => 'Jalankan pengujian sesuai skenario', 'expected' => 'Hasil pengujian sesuai ekspektasi']];
            } else {
                foreach ($tc['steps'] as &$step) {
                    if (!is_array($step)) {
                        $step = ['step' => is_string($step) ? $step : '', 'expected' => ''];
                    }
                    $step['step']     = $step['step'] ?? '';
                    $step['expected'] = $step['expected'] ?? '';
                }
            }

            // Normalize dynamic fields (handling both camelCase and snake_case)
            $tc['caseType']           = $tc['caseType'] ?? $tc['case_type'] ?? 'Positive';
            $tc['priority']           = $tc['priority'] ?? 'Medium';
            $tc['moduleFeature']      = $tc['moduleFeature'] ?? $tc['module_feature'] ?? '';
            $tc['feature']            = $tc['feature'] ?? '';
            $tc['refRequirement']     = $tc['refRequirement'] ?? $tc['ref_requirement'] ?? '';
            $tc['testData']           = $tc['testData'] ?? $tc['test_data'] ?? '';
            $tc['integrationPoint']   = $tc['integrationPoint'] ?? $tc['integration_point'] ?? '';
            $tc['dependencies']       = $tc['dependencies'] ?? '';
            $tc['relatedFeature']     = $tc['relatedFeature'] ?? $tc['related_feature'] ?? '';
            $tc['reasonLinked']       = $tc['reasonLinked'] ?? $tc['reason_linked'] ?? '';
            $tc['testTypePerf']       = $tc['testTypePerf'] ?? $tc['test_type_perf'] ?? 'Load Test';
            $tc['testParams']         = $tc['testParams'] ?? $tc['test_params'] ?? '';
            $tc['testTypeRel']        = $tc['testTypeRel'] ?? $tc['test_type_rel'] ?? 'Failure Rate';
            $tc['durationFailure']    = $tc['durationFailure'] ?? $tc['duration_failure'] ?? '';
            $tc['testTypeStress']     = $tc['testTypeStress'] ?? $tc['test_type_stress'] ?? 'Load Stress';
            $tc['stressParams']       = $tc['stressParams'] ?? $tc['stress_params'] ?? '';
            $tc['moduleFeatureNeg']   = $tc['moduleFeatureNeg'] ?? $tc['module_feature_neg'] ?? '';
            $tc['testDataNeg']        = $tc['testDataNeg'] ?? $tc['test_data_neg'] ?? '';
            $tc['validationType']     = $tc['validationType'] ?? $tc['validation_type'] ?? 'Input Validation';
            $tc['validationCriteria'] = $tc['validationCriteria'] ?? $tc['validation_criteria'] ?? '';
            $tc['componentSubsystem'] = $tc['componentSubsystem'] ?? $tc['component_subsystem'] ?? '';
            $tc['parameterMeasured']  = $tc['parameterMeasured'] ?? $tc['parameter_measured'] ?? '';
            $tc['unit']               = $tc['unit'] ?? '';
            $tc['passLimitSpec']      = $tc['passLimitSpec'] ?? $tc['pass_limit_spec'] ?? '';
            $tc['specSource']         = $tc['specSource'] ?? $tc['spec_source'] ?? '';
            $tc['measuredValue']      = $tc['measuredValue'] ?? $tc['measured_value'] ?? '';
            $tc['deviceTested']       = $tc['deviceTested'] ?? $tc['device_tested'] ?? '';
            $tc['environmentSpec']    = $tc['environmentSpec'] ?? $tc['environment_spec'] ?? '';
        }

        return $data;
    }

    private function buildFallbackFromRawText(string $rawText, string $testType): array
    {
        $rawText = trim($rawText);
        $isJsonLike = str_contains($rawText, '"report_name"') || str_contains($rawText, '"test_cases"') || str_starts_with($rawText, '{');

        $reportName = 'QA Test Report';
        $suggestedProject = '';
        $description = 'Laporan hasil analisis data mentah';

        if ($isJsonLike) {
            if (preg_match('/"report_name"\s*:\s*"([^"]+)"/i', $rawText, $m)) {
                $reportName = $m[1];
            }
            if (preg_match('/"suggested_project_name"\s*:\s*"([^"]+)"/i', $rawText, $m)) {
                $suggestedProject = $m[1];
            }
            if (preg_match('/"description"\s*:\s*"([^"]+)"/i', $rawText, $m)) {
                $description = $m[1];
            }
        } else {
            if (preg_match('/^\s*([A-Za-z0-9_\-\s]{2,30})\s*:/m', $rawText, $pm)) {
                $suggestedProject = trim($pm[1]);
                $reportName = "{$suggestedProject} QA Test Report";
            }
        }

        // Section breakdown logic (bullet point or line group parser)
        $sections = [];
        $lines = explode("\n", strip_tags($rawText));
        $currentSection = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            if ($isJsonLike) {
                if (preg_match('/^\s*"(report_name|suggested_project_name|test_type|report_date|description|test_cases|testTitle|status|steps)"\s*:/i', $trimmed)) {
                    continue;
                }
                if (in_array($trimmed, ['{', '}', '[', ']', '],', '},'])) {
                    continue;
                }
                $trimmed = preg_replace('/^"|"(?:,\s*)?$/', '', $trimmed);
            }

            if (preg_match('/^(?:[\-\*•]|\d+[\.\)])\s*([^\:\n]+)(?:\:)?(.*)$/', $trimmed, $m) && !str_starts_with($line, '        ')) {
                if ($currentSection) {
                    $sections[] = $currentSection;
                }
                $currentSection = [
                    'title' => trim($m[1]),
                    'lines' => array_filter([trim($m[2])])
                ];
            } else {
                if ($currentSection) {
                    $currentSection['lines'][] = $trimmed;
                } else {
                    $currentSection = [
                        'title' => substr($trimmed, 0, 60),
                        'lines' => [$trimmed]
                    ];
                }
            }
        }
        if ($currentSection) {
            $sections[] = $currentSection;
        }

        $testCases = [];

        foreach ($sections as $idx => $sec) {
            $secTitle = $sec['title'];
            $secText  = implode("\n", $sec['lines']);

            $status = 'PASS';
            if (preg_match('/(error|404|gabisa|gagal|missing|belum|dummy|issue|bug|pertanyakan|gatau)/i', $secTitle . ' ' . $secText)) {
                $status = 'FAILED';
            }
            if (preg_match('/(404|blocker|down|ga terhubung)/i', $secTitle . ' ' . $secText)) {
                $status = 'FAILED';
            }

            $steps = [];
            $rawSteps = array_filter($sec['lines']);
            foreach ($rawSteps as $st) {
                $cleanSt = preg_replace('/^[\-\*•\d\.\)\s]+/', '', $st);
                if (strlen($cleanSt) > 3) {
                    $steps[] = [
                        'step' => $cleanSt,
                        'expected' => 'Sistem merespons sesuai spesifikasi'
                    ];
                }
            }
            if (empty($steps)) {
                $steps[] = [
                    'step' => "Uji modul {$secTitle}",
                    'expected' => 'Sistem berfungsi sesuai spesifikasi'
                ];
            }

            $testCases[] = [
                'testTitle'    => $secTitle ?: "Test Case " . ($idx + 1),
                'precondition' => 'Data mentah diisi oleh tester',
                'status'       => $status,
                'testResult'   => $status === 'PASS' ? 'PASSED' : $status,
                'actualResult' => $secText ?: "Pengujian modul {$secTitle}",
                'notesQA'      => 'Disusun dari data mentah tester',
                'steps'        => array_slice($steps, 0, 10),
            ];
        }

        if (empty($testCases)) {
            $testCases[] = [
                'testTitle'    => $reportName,
                'precondition' => 'Data mentah diisi oleh tester',
                'status'       => 'PASS',
                'testResult'   => 'PASSED',
                'actualResult' => $description,
                'notesQA'      => 'Disusun dari data mentah tester',
                'steps'        => [['step' => 'Lakukan pengujian sesuai data mentah', 'expected' => 'Sistem berfungsi sesuai ekspektasi']]
            ];
        }

        return [
            'report_name'            => $reportName,
            'suggested_project_name' => $suggestedProject,
            'test_type'              => $testType,
            'report_date'            => date('Y-m-d'),
            'description'            => $description,
            'test_cases'             => $testCases,
        ];
    }

    /**
     * Get available providers and their config status.
     */
    public static function getProvidersStatus(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $geminiKey     = $user ? $user->getResolvedGeminiKey() : config('ai.gemini.api_key');
        $openrouterKey   = $user ? $user->getResolvedOpenRouterKey() : config('ai.openrouter.api_key');
        $openrouterModel = $user ? $user->getResolvedOpenRouterModel() : config('ai.openrouter.model', 'meta-llama/llama-3.1-8b-instruct:free');
        $ollamaUrl       = $user ? $user->getResolvedOllamaBaseUrl() : config('ai.ollama.base_url');
        $ollamaModel     = $user ? $user->getResolvedOllamaModel() : config('ai.ollama.model');
        $omnirouteKey    = $user ? $user->getResolvedOmnirouteKey() : config('ai.omniroute.api_key');
        $omnirouteUrl    = $user ? $user->getResolvedOmnirouteBaseUrl() : config('ai.omniroute.base_url');
        $omnirouteModel  = $user ? $user->getResolvedOmnirouteModel() : config('ai.omniroute.model');
        $openaiKey       = $user ? $user->getResolvedOpenAiKey() : config('ai.openai.api_key');
        $openaiUrl       = $user ? $user->getResolvedOpenAiBaseUrl() : config('ai.openai.base_url');
        $openaiModel     = $user ? $user->getResolvedOpenAiModel() : config('ai.openai.model');
        $defaultProv     = $user ? $user->getResolvedDefaultAiProvider() : config('ai.default_provider');

        return [
            'default_provider' => $defaultProv,
            'gemini' => [
                'label'       => 'Google Gemini',
                'icon'        => 'google',
                'configured'  => !empty($geminiKey),
                'model'       => config('ai.gemini.model', 'gemini-1.5-flash'),
                'has_key'     => !empty($geminiKey),
                'key_masked'  => !empty($geminiKey) ? substr($geminiKey, 0, 6) . '...' . substr($geminiKey, -4) : '',
                'description' => 'Cepat, akurat, gratis tier tersedia',
            ],
            'openrouter' => [
                'label'       => 'OpenRouter',
                'icon'        => 'cloud',
                'configured'  => !empty($openrouterKey),
                'model'       => $openrouterModel,
                'has_key'     => !empty($openrouterKey),
                'key_masked'  => !empty($openrouterKey) ? substr($openrouterKey, 0, 6) . '...' . substr($openrouterKey, -4) : '',
                'description' => 'Multi-model, ada pilihan gratis',
            ],
            'ollama' => [
                'label'       => 'Ollama (Lokal)',
                'icon'        => 'laptop',
                'configured'  => true,
                'model'       => $ollamaModel,
                'base_url'    => $ollamaUrl,
                'description' => '100% offline, privasi terjaga',
            ],
            'omniroute' => [
                'label'       => 'OmniRoute Server',
                'icon'        => 'server',
                'configured'  => true,
                'model'       => $omnirouteModel,
                'base_url'    => $omnirouteUrl,
                'has_key'     => !empty($omnirouteKey),
                'key_masked'  => !empty($omnirouteKey) ? substr($omnirouteKey, 0, 6) . '...' . substr($omnirouteKey, -4) : '',
                'description' => 'Multi-model AI router (202.155.143.157:20128)',
            ],
            'openai' => [
                'label'       => 'OpenAI / Celeris',
                'icon'        => 'bolt',
                'configured'  => !empty($openaiKey),
                'model'       => $openaiModel,
                'base_url'    => $openaiUrl,
                'has_key'     => !empty($openaiKey),
                'key_masked'  => !empty($openaiKey) ? substr($openaiKey, 0, 6) . '...' . substr($openaiKey, -4) : '',
                'description' => 'OpenAI-compatible / Custom endpoint (Celeris)',
            ],
        ];
    }
}
