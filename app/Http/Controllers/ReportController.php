<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use App\Models\TestReport;
use App\Models\TestCase;
use App\Models\TestStep;
use App\Models\Bug;
use App\Services\AiService;
use App\Exports\QaReportExport;
use App\Exports\QaTpl001MasterExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Return available AI providers and their config status (for modal UI).
     */
    public function aiProviders()
    {
        return response()->json(AiService::getProvidersStatus());
    }

    /**
     * Parse raw text via AI and return structured JSON for form auto-fill.
     */
    public function aiParse(Request $request)
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');

        $request->validate([
            'raw_text'  => 'required|string|min:10|max:10000',
            'provider'  => 'required|in:gemini,openrouter,ollama,omniroute,openai',
            'test_type' => 'nullable|string|in:functional,integration,regression,performance,reliability,stress,negative,validation,hardware',
            'api_key'   => 'nullable|string',   // allow runtime override
            'model'     => 'nullable|string',    // allow custom model
            'base_url'  => 'nullable|string',    // allow custom base url
        ]);

        // Allow per-request API key override (from modal UI)
        if ($request->filled('api_key')) {
            config(["ai.{$request->provider}.api_key" => $request->api_key]);
        }
        if ($request->filled('model')) {
            config(["ai.{$request->provider}.model" => $request->model]);
        }
        if ($request->filled('base_url')) {
            config(["ai.{$request->provider}.base_url" => $request->base_url]);
        }

        try {
            $testType = $request->input('test_type', 'functional');
            $service  = new AiService($request->provider);
            $result   = $service->parse($request->raw_text, $testType);

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[ReportController::aiParse] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Export QA testing report as Master QA-TPL-001 (or multi-type legacy) Excel file.
     *
     * @param  int|null $id Optional TestReport ID
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export($id = null)
    {
        $fileName = 'Report_Testing_QA.xlsx';

        if ($id) {
            $report = TestReport::with(['project', 'createdBy', 'testCases.steps'])->findOrFail($id);
            $projCode = $report->project->project_code ?? 'QA';
            $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $report->report_name);
            $dateStr = $report->report_date ? $report->report_date->format('Ymd') : date('Ymd');
            $fileName = "{$projCode}_TC_BugList_{$sanitizedName}_{$dateStr}.xlsx";

            // If user explicitly asks for legacy multi-type export (?format=types)
            if (request('format') === 'types') {
                return Excel::download(new QaReportExport((int)$id), "QA_Types_{$sanitizedName}.xlsx");
            }

            return Excel::download(new QaTpl001MasterExport($report), $fileName);
        }

        return Excel::download(new QaReportExport(null), $fileName);
    }

    /**
     * Display a listing of all test reports.
     */
    public function index(Request $request)
    {
        $query = TestReport::with(['project', 'createdBy'])
            ->withCount('testCases')
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('report_name', 'LIKE', "%{$search}%")
                  ->orWhere('sprint', 'LIKE', "%{$search}%")
                  ->orWhereHas('project', fn($pq) => $pq->where('name', 'LIKE', "%{$search}%")->orWhere('project_code', 'LIKE', "%{$search}%"));
            });
        }

        // Test type filter
        if ($request->filled('test_type')) {
            $query->where('test_type', $request->test_type);
        }

        // Sprint filter
        if ($request->filled('sprint')) {
            $query->where('sprint', $request->sprint);
        }

        $reports = $query->paginate(10)->withQueryString();

        // Stats
        $stats = [
            'total'   => TestReport::count(),
            'pass'    => 0,
            'failed'  => 0,
            'blocked' => 0,
            'not_run' => 0,
            'na'      => 0,
        ];

        // Calculate overall pass/fail across all test cases
        $statusCounts = TestCase::whereNotNull('report_id')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats['pass']    = ($statusCounts['Pass'] ?? 0) + ($statusCounts['PASS'] ?? 0);
        $stats['failed']  = ($statusCounts['Fail'] ?? 0) + ($statusCounts['FAILED'] ?? 0);
        $stats['blocked'] = ($statusCounts['Blocked'] ?? 0) + ($statusCounts['BLOCKED'] ?? 0);
        $stats['not_run'] = ($statusCounts['Not Run'] ?? 0) + ($statusCounts['NOT_RUN'] ?? 0);
        $stats['na']      = ($statusCounts['N/A'] ?? 0) + ($statusCounts['NA'] ?? 0);

        $totalCases = array_sum($statusCounts->toArray());
        $passRate   = $totalCases > 0 ? round(($stats['pass'] / $totalCases) * 100) : 0;

        return view('pages.report.index', compact('reports', 'stats', 'passRate'));
    }

    /**
     * Show the form for creating a new report.
     */
    public function create()
    {
        $projects = Projects::orderBy('name')->get();
        $latestReports = TestReport::select('project_id', 'sprint', 'cycle', 'test_type')
            ->whereNotNull('project_id')
            ->orderByDesc('created_at')
            ->get()
            ->unique('project_id')
            ->keyBy('project_id');

        return view('pages.report.create', compact('projects', 'latestReports'));
    }

    /**
     * Store a new report with multiple test cases and steps.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id'                => 'required|exists:projects,id',
            'test_type'                 => 'required|in:functional,integration,regression,performance,reliability,stress,negative,validation,hardware',
            'report_date'               => 'required|date',
            'report_name'               => 'required|string|max:255',
            'sprint'                    => 'nullable|string|max:50',
            'cycle'                     => 'nullable|string|max:50',
            'description'               => 'nullable|string',
            'test_cases'                => 'nullable|array',
            'test_cases.*.testTitle'    => 'required_with:test_cases|string|max:255',
            'test_cases.*.status'       => 'nullable|string',
        ], [
            'project_id.required'             => 'Project harus dipilih.',
            'report_name.required'            => 'Nama report tidak boleh kosong.',
            'test_type.required'              => 'Tipe testing harus dipilih.',
        ]);

        $report = DB::transaction(function () use ($request) {
            // 1. Create the parent report
            $report = TestReport::create([
                'project_id'  => $request->project_id,
                'created_by'  => Auth::id(),
                'report_name' => $request->report_name,
                'sprint'      => $request->sprint ? trim($request->sprint) : null,
                'cycle'       => $request->cycle ? trim($request->cycle) : 'Siklus 1',
                'test_type'   => $request->test_type,
                'report_date' => $request->report_date,
                'description' => $request->description ?? null,
            ]);

            // 2. Create each test case and its steps if provided
            if ($request->filled('test_cases') && is_array($request->test_cases)) {
                foreach ($request->test_cases as $caseData) {
                    $dynamicFields = $this->extractDynamicFields($caseData, $request->test_type);
                    $rawStatus     = $caseData['status'] ?? 'Not Executed';
                    $cleanStatus   = match(strtoupper(str_replace([' ', '_', '/'], '', (string)$rawStatus))) {
                        'PASS' => 'Pass',
                        'FAIL', 'FAILED' => 'Fail',
                        'BLOCKED' => 'Blocked',
                        'NOTRUN', 'NOTEXECUTED' => 'Not Executed',
                        'NA' => 'N/A',
                        default => in_array($rawStatus, ['Not Executed', 'Not Run', 'Pass', 'Fail', 'Blocked', 'N/A']) ? $rawStatus : 'Not Executed',
                    };

                    $isAi = !empty($caseData['is_ai_generated']);
                    $reviewStatus = $caseData['review_status'] ?? ($isAi ? 'needs_review' : 'reviewed');

                    $testCase = TestCase::create([
                        'report_id'         => $report->id,
                        'project_id'        => $request->project_id,
                        'test_type'         => $request->test_type,
                        'title'             => $caseData['testTitle'],
                        'test_case_id'      => $caseData['testCaseId'] ?? $caseData['test_case_id'] ?? null,
                        'priority'          => $caseData['priority'] ?? 'Medium',
                        'precondition'      => $caseData['precondition'] ?? null,
                        'dynamic_fields'    => $dynamicFields,
                        'status'            => $cleanStatus,
                        'actual_result'     => $caseData['actualResult'] ?? null,
                        'notes_qa'          => $caseData['notesQA'] ?? null,
                        'notes_programmer'  => $caseData['notesProgrammer'] ?? null,
                        'buckleup_ticket'   => $caseData['buckleupTicket'] ?? $caseData['buckleup_ticket'] ?? null,
                        'bug_ticket'        => $caseData['bugTicket'] ?? $caseData['bug_ticket'] ?? null,
                        'evidence_url'      => $caseData['evidenceUrl'] ?? null,
                        'is_ai_generated'   => $isAi,
                        'review_status'     => $reviewStatus,
                        'date_tested'       => $request->report_date,
                        'tested_by'         => Auth::id(),
                    ]);

                    // 3. Create steps for this test case
                    if (!empty($caseData['steps']) && is_array($caseData['steps'])) {
                        $stepOrder = 1;
                        foreach ($caseData['steps'] as $stepData) {
                            $desc = is_array($stepData) ? ($stepData['step'] ?? $stepData['step_description'] ?? '') : (string)$stepData;
                            if (!empty(trim($desc))) {
                                TestStep::create([
                                    'test_case_id'     => $testCase->id,
                                    'step_order'       => $stepOrder++,
                                    'step_description' => $desc,
                                    'expected_result'  => is_array($stepData) ? ($stepData['expected'] ?? $stepData['expected_result'] ?? '') : '',
                                    'actual_result'    => is_array($stepData) ? ($stepData['actual_result'] ?? $stepData['actual'] ?? '') : '',
                                    'status'           => is_array($stepData) ? ($stepData['status'] ?? 'Not Executed') : 'Not Executed',
                                ]);
                            }
                        }
                    }
                }
            }

            return $report;
        });

        $caseCount = is_array($request->test_cases) ? count($request->test_cases) : 0;
        $msg = $caseCount > 0
            ? "Report \"{$report->report_name}\" berhasil disimpan dengan {$caseCount} test case."
            : "Report \"{$report->report_name}\" berhasil dibuat! Silakan tambahkan test case menggunakan AI, import CSV, atau manual.";

        return redirect()
            ->route('reports.show', $report->id)
            ->with('success', $msg);
    }

    /**
     * Display the specified report with Quality Gate & Completeness Check (QA-TPL-001).
     */
    public function show($id)
    {
        $report = TestReport::with(['project', 'createdBy', 'testCases.steps', 'bugs'])->findOrFail($id);
        $qg     = $report->getQualityGateMetrics();

        // Also fetch bugs associated with this report or test cases
        $tcIds = $report->testCases->pluck('test_case_id')->filter()->values()->all();
        $bugs = Bug::where('report_id', $report->id)
            ->orWhere(function ($q) use ($report, $tcIds) {
                $q->where('project_id', $report->project_id);
                if (!empty($tcIds)) {
                    $q->whereIn('related_test_case_id', $tcIds);
                }
            })
            ->latest()
            ->get();

        return view('pages.report.show', compact('report', 'qg', 'bugs'));
    }

    /**
     * Show the form for editing the specified report.
     */
    public function edit($id)
    {
        $report   = TestReport::with(['project', 'testCases.steps'])->findOrFail($id);
        $projects = Projects::orderBy('name')->get();

        return view('pages.report.edit', compact('report', 'projects'));
    }

    /**
     * Update the specified report and all its test cases.
     */
    public function update(Request $request, $id)
    {
        $report = TestReport::findOrFail($id);

        $request->validate([
            'project_id'                => 'required|exists:projects,id',
            'test_type'                 => 'required|in:functional,integration,regression,performance,reliability,stress,negative,validation,hardware',
            'report_date'               => 'required|date',
            'report_name'               => 'required|string|max:255',
            'sprint'                    => 'nullable|string|max:50',
            'cycle'                     => 'nullable|string|max:50',
            'test_cases'                => 'required|array|min:1',
            'test_cases.*.testTitle'    => 'required|string|max:255',
            'test_cases.*.status'       => 'required|string',
        ], [
            'project_id.required'             => 'Project harus dipilih.',
            'report_name.required'            => 'Nama report tidak boleh kosong.',
            'test_type.required'              => 'Tipe testing harus dipilih.',
            'test_cases.required'             => 'Minimal harus ada 1 test case.',
            'test_cases.*.testTitle.required' => 'Title test case tidak boleh kosong.',
            'test_cases.*.status.required'    => 'Status test case harus dipilih.',
        ]);

        DB::transaction(function () use ($request, $report) {
            // 1. Update the parent report
            $report->update([
                'project_id'  => $request->project_id,
                'report_name' => $request->report_name,
                'sprint'      => $request->sprint ? trim($request->sprint) : null,
                'cycle'       => $request->cycle ? trim($request->cycle) : 'Siklus 1',
                'test_type'   => $request->test_type,
                'report_date' => $request->report_date,
                'description' => $request->description ?? null,
            ]);

            // 2. Delete all old test cases and their steps (cascade handles steps)
            $report->testCases()->each(function ($tc) {
                $tc->steps()->delete();
                $tc->delete();
            });

            // 3. Recreate all test cases and steps from submitted data
            foreach ($request->test_cases as $caseData) {
                $dynamicFields = $this->extractDynamicFields($caseData, $request->test_type);
                $rawStatus     = $caseData['status'] ?? 'Pass';
                $cleanStatus   = match(strtoupper(str_replace([' ', '_', '/'], '', $rawStatus))) {
                    'PASS' => 'Pass',
                    'FAIL', 'FAILED' => 'Fail',
                    'BLOCKED' => 'Blocked',
                    'NOTRUN' => 'Not Run',
                    'NA' => 'N/A',
                    default => in_array($rawStatus, ['Not Run', 'Pass', 'Fail', 'Blocked', 'N/A']) ? $rawStatus : 'Pass',
                };

                $testCase = TestCase::create([
                    'report_id'         => $report->id,
                    'project_id'        => $request->project_id,
                    'test_type'         => $request->test_type,
                    'title'             => $caseData['testTitle'],
                    'test_case_id'      => $caseData['testCaseId'] ?? $caseData['test_case_id'] ?? null,
                    'priority'          => $caseData['priority'] ?? 'Medium',
                    'precondition'      => $caseData['precondition'] ?? null,
                    'dynamic_fields'    => $dynamicFields,
                    'status'            => $cleanStatus,
                    'actual_result'     => $caseData['actualResult'] ?? null,
                    'notes_qa'          => $caseData['notesQA'] ?? null,
                    'notes_programmer'  => $caseData['notesProgrammer'] ?? null,
                    'buckleup_ticket'   => $caseData['buckleupTicket'] ?? $caseData['buckleup_ticket'] ?? null,
                    'bug_ticket'        => $caseData['bugTicket'] ?? $caseData['bug_ticket'] ?? null,
                    'evidence_url'      => $caseData['evidenceUrl'] ?? null,
                    'date_tested'       => $request->report_date,
                    'tested_by'         => Auth::id(),
                ]);

                if (!empty($caseData['steps']) && is_array($caseData['steps'])) {
                    $stepOrder = 1;
                    foreach ($caseData['steps'] as $stepData) {
                        if (!empty($stepData['step'])) {
                            TestStep::create([
                                'test_case_id'     => $testCase->id,
                                'step_order'       => $stepOrder++,
                                'step_description' => $stepData['step'],
                                'expected_result'  => $stepData['expected'] ?? '',
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()
            ->route('reports.index')
            ->with('success', "Report \"{$report->report_name}\" berhasil diperbarui!");
    }

    /**
     * Remove the specified report and all its test cases.
     */
    public function destroy($id)
    {
        $report = TestReport::with('testCases.steps')->findOrFail($id);
        $name   = $report->report_name;

        DB::transaction(function () use ($report) {
            $report->testCases()->each(function ($tc) {
                $tc->steps()->delete();
                $tc->delete();
            });
            $report->delete();
        });

        return redirect()
            ->route('reports.index')
            ->with('success', "Report \"{$name}\" berhasil dihapus.");
    }

    /**
     * Extract type-specific dynamic fields from submitted form data.
     * Aligned with QA-TPL-001 Master Template v0.2 (Software & Hardware)
     */
    private function extractDynamicFields(array $data, string $testType): array
    {
        // Common universal fields across types
        $commonKeys = ['caseType', 'refRequirement', 'testData'];

        $typeKeys = match ($testType) {
            'functional'  => ['moduleFeature', 'feature'],
            'integration' => ['integrationPoint', 'dependencies'],
            'regression'  => ['relatedFeature', 'reasonLinked'],
            'performance' => ['testTypePerf', 'testParams'],
            'reliability' => ['testTypeRel', 'durationFailure'],
            'stress'      => ['testTypeStress', 'stressParams'],
            'negative'    => ['moduleFeatureNeg', 'testDataNeg'],
            'validation'  => ['validationType', 'validationCriteria'],
            'hardware'    => [
                'componentSubsystem', 'criticality', 'parameterMeasured',
                'unit', 'passLimitSpec', 'specSource', 'measuredValue',
                'cycleNum', 'valueBefore', 'valueAfter', 'duration',
                'envConditions', 'startTime', 'endTime', 'engineerNotes',
                'deviceTested', 'environmentSpec'
            ],
            default       => [],
        };

        $dynamicKeys = array_unique(array_merge($commonKeys, $typeKeys));

        $fields = [];
        foreach ($dynamicKeys as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $fields[$key] = $data[$key];
            }
        }

        if (!empty($data['testResult'])) {
            $fields['testResult'] = $data['testResult'];
        }

        return $fields;
    }

    /**
     * Parse raw text via AI and return structured JSON for report Telegram Senna.
     */
    public function parseBuatSennaReport(Request $request)
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');

        $request->validate([
            'raw_text'  => 'required|string|min:10|max:10000',
            'provider'  => 'required|in:gemini,openrouter,ollama,omniroute',
            'api_key'   => 'nullable|string',   // allow runtime override
            'model'     => 'nullable|string',    // allow custom model
        ]);

        // Allow per-request API key override (from modal UI)
        if ($request->filled('api_key')) {
            config(["ai.{$request->provider}.api_key" => $request->api_key]);
        }
        if ($request->filled('model')) {
            config(["ai.{$request->provider}.model" => $request->model]);
        }

        try {
            $service  = new AiService($request->provider);
            $result   = $service->parseSenna($request->raw_text, $testType);

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[ReportController::aiParse] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store a new test case for a report.
     */
    public function storeTestCase(Request $request, $reportId)
    {
        $report = TestReport::with('project')->findOrFail($reportId);

        $request->validate([
            'title'             => 'required|string|max:255',
            'test_case_id'      => 'nullable|string|max:100',
            'priority'          => 'nullable|string|in:Low,Medium,High,Critical',
            'status'            => 'nullable|string',
            'precondition'      => 'nullable|string',
            'actual_result'     => 'nullable|string',
            'notes_qa'          => 'nullable|string',
            'notes_programmer'  => 'nullable|string',
            'buckleup_ticket'   => 'nullable|string|max:100',
            'bug_ticket'        => 'nullable|string|max:100',
            'evidence_url'      => 'nullable|string|max:1000',
            'steps'             => 'nullable|array',
            'dynamic_fields'    => 'nullable|array',
            'is_ai_generated'   => 'nullable|boolean',
            'review_status'     => 'nullable|string|in:needs_review,reviewed',
        ]);

        $rawStatus   = $request->input('status', 'Not Executed');
        $cleanStatus = match(strtoupper(str_replace([' ', '_', '/'], '', (string)$rawStatus))) {
            'PASS' => 'Pass',
            'FAIL', 'FAILED' => 'Fail',
            'BLOCKED' => 'Blocked',
            'NOTRUN', 'NOTEXECUTED' => 'Not Executed',
            'NA' => 'N/A',
            default => in_array($rawStatus, ['Not Executed', 'Not Run', 'Pass', 'Fail', 'Blocked', 'N/A']) ? $rawStatus : 'Not Executed',
        };

        // Auto TC ID if not provided
        $tcId = $request->test_case_id;
        if (empty($tcId)) {
            $code = $report->project->project_code ?? 'TC';
            $num = $report->testCases()->count() + 1;
            $tcId = sprintf('%s-TC-%03d', $code, $num);
        }

        $isAi = $request->boolean('is_ai_generated', false);
        $reviewStatus = $request->input('review_status', $isAi ? 'needs_review' : 'reviewed');

        $testCase = DB::transaction(function () use ($request, $report, $cleanStatus, $tcId, $isAi, $reviewStatus) {
            $tc = TestCase::create([
                'report_id'         => $report->id,
                'project_id'        => $report->project_id,
                'test_type'         => $report->test_type,
                'title'             => $request->title,
                'test_case_id'      => $tcId,
                'priority'          => $request->priority ?: 'Medium',
                'precondition'      => $request->precondition,
                'dynamic_fields'    => $request->dynamic_fields ?? [],
                'status'            => $cleanStatus,
                'actual_result'     => $request->actual_result,
                'notes_qa'          => $request->notes_qa,
                'notes_programmer'  => $request->notes_programmer,
                'buckleup_ticket'   => $request->buckleup_ticket,
                'bug_ticket'        => $request->bug_ticket,
                'evidence_url'      => $request->evidence_url,
                'is_ai_generated'   => $isAi,
                'review_status'     => $reviewStatus,
                'date_tested'       => $report->report_date ?: now(),
                'tested_by'         => Auth::id(),
            ]);

            if ($request->has('steps') && is_array($request->steps)) {
                $order = 1;
                foreach ($request->steps as $s) {
                    $desc = is_array($s) ? ($s['step_description'] ?? $s['step'] ?? '') : (string)$s;
                    if (!empty(trim($desc))) {
                        TestStep::create([
                            'test_case_id'     => $tc->id,
                            'step_order'       => $order++,
                            'step_description' => $desc,
                            'expected_result'  => is_array($s) ? ($s['expected_result'] ?? $s['expected'] ?? '') : '',
                            'actual_result'    => is_array($s) ? ($s['actual_result'] ?? $s['actual'] ?? '') : '',
                            'status'           => is_array($s) ? ($s['status'] ?? 'Not Executed') : 'Not Executed',
                        ]);
                    }
                }
            }

            return $tc;
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Test case berhasil ditambahkan.',
            'data'      => $testCase->load('steps'),
            'qg'        => $report->fresh()->getQualityGateMetrics(),
        ]);
    }

    /**
     * Update an existing test case (supports autosave).
     */
    public function updateTestCase(Request $request, $reportId, $testCaseId)
    {
        $report   = TestReport::findOrFail($reportId);
        $testCase = TestCase::where('report_id', $reportId)->findOrFail($testCaseId);

        $request->validate([
            'title'             => 'sometimes|required|string|max:255',
            'test_case_id'      => 'nullable|string|max:100',
            'priority'          => 'nullable|string|in:Low,Medium,High,Critical',
            'status'            => 'nullable|string',
            'precondition'      => 'nullable|string',
            'actual_result'     => 'nullable|string',
            'notes_qa'          => 'nullable|string',
            'notes_programmer'  => 'nullable|string',
            'buckleup_ticket'   => 'nullable|string|max:100',
            'bug_ticket'        => 'nullable|string|max:100',
            'evidence_url'      => 'nullable|string|max:1000',
            'steps'             => 'nullable|array',
            'dynamic_fields'    => 'nullable|array',
            'review_status'     => 'nullable|string|in:needs_review,reviewed',
        ]);

        $updateData = [];
        if ($request->has('title'))            $updateData['title'] = $request->title;
        if ($request->has('test_case_id'))     $updateData['test_case_id'] = $request->test_case_id;
        if ($request->has('priority'))         $updateData['priority'] = $request->priority;
        if ($request->has('precondition'))     $updateData['precondition'] = $request->precondition;
        if ($request->has('actual_result'))    $updateData['actual_result'] = $request->actual_result;
        if ($request->has('notes_qa'))         $updateData['notes_qa'] = $request->notes_qa;
        if ($request->has('notes_programmer')) $updateData['notes_programmer'] = $request->notes_programmer;
        if ($request->has('buckleup_ticket'))  $updateData['buckleup_ticket'] = $request->buckleup_ticket;
        if ($request->has('bug_ticket'))       $updateData['bug_ticket'] = $request->bug_ticket;
        if ($request->has('evidence_url'))     $updateData['evidence_url'] = $request->evidence_url;
        if ($request->has('review_status'))    $updateData['review_status'] = $request->review_status;

        if ($request->has('status')) {
            $rawStatus = $request->status;
            $updateData['status'] = match(strtoupper(str_replace([' ', '_', '/'], '', (string)$rawStatus))) {
                'PASS' => 'Pass',
                'FAIL', 'FAILED' => 'Fail',
                'BLOCKED' => 'Blocked',
                'NOTRUN', 'NOTEXECUTED' => 'Not Executed',
                'NA' => 'N/A',
                default => in_array($rawStatus, ['Not Executed', 'Not Run', 'Pass', 'Fail', 'Blocked', 'N/A']) ? $rawStatus : 'Not Executed',
            };
            $updateData['date_tested'] = now();
            $updateData['tested_by']   = Auth::id();
        }

        if ($request->has('dynamic_fields')) {
            $existing = is_array($testCase->dynamic_fields) ? $testCase->dynamic_fields : ($testCase->dynamic_fields?->toArray() ?? []);
            $updateData['dynamic_fields'] = array_merge($existing, $request->dynamic_fields);
        }

        DB::transaction(function () use ($testCase, $updateData, $request) {
            $testCase->update($updateData);

            if ($request->has('steps') && is_array($request->steps)) {
                $testCase->steps()->delete();
                $order = 1;
                foreach ($request->steps as $s) {
                    $desc = is_array($s) ? ($s['step_description'] ?? $s['step'] ?? '') : (string)$s;
                    if (!empty(trim($desc))) {
                        TestStep::create([
                            'test_case_id'     => $testCase->id,
                            'step_order'       => $order++,
                            'step_description' => $desc,
                            'expected_result'  => is_array($s) ? ($s['expected_result'] ?? $s['expected'] ?? '') : '',
                            'actual_result'    => is_array($s) ? ($s['actual_result'] ?? $s['actual'] ?? '') : '',
                            'status'           => is_array($s) ? ($s['status'] ?? 'Not Executed') : 'Not Executed',
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Tersimpan otomatis.',
            'data'      => $testCase->fresh('steps'),
            'qg'        => $report->fresh()->getQualityGateMetrics(),
        ]);
    }

    /**
     * Delete a single test case.
     */
    public function destroyTestCase($reportId, $testCaseId)
    {
        $report   = TestReport::findOrFail($reportId);
        $testCase = TestCase::where('report_id', $reportId)->findOrFail($testCaseId);

        DB::transaction(function () use ($testCase) {
            $testCase->steps()->delete();
            $testCase->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Test case berhasil dihapus.',
            'qg'      => $report->fresh()->getQualityGateMetrics(),
        ]);
    }

    /**
     * Bulk update status for multiple test cases.
     */
    public function bulkStatusTestCase(Request $request, $reportId)
    {
        $report = TestReport::findOrFail($reportId);
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'status' => 'required|string',
        ]);

        $rawStatus = $request->status;
        $cleanStatus = match(strtoupper(str_replace([' ', '_', '/'], '', (string)$rawStatus))) {
            'PASS' => 'Pass',
            'FAIL', 'FAILED' => 'Fail',
            'BLOCKED' => 'Blocked',
            'NOTRUN', 'NOTEXECUTED' => 'Not Executed',
            'NA' => 'N/A',
            default => in_array($rawStatus, ['Not Executed', 'Not Run', 'Pass', 'Fail', 'Blocked', 'N/A']) ? $rawStatus : 'Not Executed',
        };

        TestCase::where('report_id', $reportId)
            ->whereIn('id', $request->ids)
            ->update([
                'status'      => $cleanStatus,
                'date_tested' => now(),
                'tested_by'   => Auth::id(),
            ]);

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' test case berhasil diubah statusnya menjadi ' . $cleanStatus,
            'qg'      => $report->fresh()->getQualityGateMetrics(),
        ]);
    }

    /**
     * Bulk delete multiple test cases.
     */
    public function bulkDeleteTestCase(Request $request, $reportId)
    {
        $report = TestReport::findOrFail($reportId);
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($request, $reportId) {
            $cases = TestCase::where('report_id', $reportId)->whereIn('id', $request->ids)->get();
            foreach ($cases as $c) {
                $c->steps()->delete();
                $c->delete();
            }
        });

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' test case berhasil dihapus.',
            'qg'      => $report->fresh()->getQualityGateMetrics(),
        ]);
    }

    /**
     * Duplicate test case(s).
     */
    public function duplicateTestCase(Request $request, $reportId)
    {
        $report = TestReport::with('project')->findOrFail($reportId);
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $created = [];
        $projCode = $report->project->project_code ?? 'TC';

        DB::transaction(function () use ($request, $report, $projCode, &$created) {
            $cases = TestCase::with('steps')->where('report_id', $report->id)->whereIn('id', $request->ids)->get();
            $totalExisting = $report->testCases()->count();

            foreach ($cases as $index => $source) {
                $num = $totalExisting + $index + 1;
                $newTcId = sprintf('%s-TC-%03d', $projCode, $num);

                $newTc = TestCase::create([
                    'report_id'         => $report->id,
                    'project_id'        => $report->project_id,
                    'test_type'         => $report->test_type,
                    'title'             => '[Duplikat] ' . $source->title,
                    'test_case_id'      => $newTcId,
                    'priority'          => $source->priority,
                    'precondition'      => $source->precondition,
                    'dynamic_fields'    => is_array($source->dynamic_fields) ? $source->dynamic_fields : ($source->dynamic_fields?->toArray() ?? []),
                    'status'            => 'Not Executed', // reset status on duplicate per QA standard
                    'actual_result'     => null,
                    'notes_qa'          => $source->notes_qa,
                    'notes_programmer'  => $source->notes_programmer,
                    'buckleup_ticket'   => $source->buckleup_ticket,
                    'bug_ticket'        => null,
                    'evidence_url'      => null,
                    'is_ai_generated'   => $source->is_ai_generated,
                    'review_status'     => $source->review_status,
                    'date_tested'       => $report->report_date ?: now(),
                    'tested_by'         => Auth::id(),
                ]);

                foreach ($source->steps as $s) {
                    $s->cloneTo($newTc->id);
                }

                $created[] = $newTc->load('steps');
            }
        });

        return response()->json([
            'success' => true,
            'message' => count($created) . ' test case berhasil diduplikat.',
            'data'    => $created,
            'qg'      => $report->fresh()->getQualityGateMetrics(),
        ]);
    }

    /**
     * Approve a single AI draft test case.
     */
    public function approveAiTestCase(Request $request, $reportId, $testCaseId)
    {
        $testCase = TestCase::where('report_id', $reportId)->findOrFail($testCaseId);
        $testCase->update(['review_status' => 'reviewed']);

        return response()->json([
            'success' => true,
            'message' => 'Test case berhasil disetujui QA.',
            'data'    => $testCase,
        ]);
    }

    /**
     * Bulk approve AI draft test cases.
     */
    public function bulkApproveAiTestCase(Request $request, $reportId)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        TestCase::where('report_id', $reportId)
            ->whereIn('id', $request->ids)
            ->update(['review_status' => 'reviewed']);

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' draft test case berhasil disetujui.',
        ]);
    }

    /**
     * Generate test cases via AI based on User Story, requirement, or feature description.
     */
    public function aiGenerateTestCases(Request $request, $reportId)
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');

        $report = TestReport::with('project')->findOrFail($reportId);

        $request->validate([
            'raw_text'       => 'required|string|min:10|max:10000',
            'provider'       => 'required|in:gemini,openrouter,ollama,omniroute,openai',
            'save_directly'  => 'nullable|boolean',
            'api_key'        => 'nullable|string',
            'model'          => 'nullable|string',
        ]);

        if ($request->filled('api_key')) {
            config(["ai.{$request->provider}.api_key" => $request->api_key]);
        }
        if ($request->filled('model')) {
            config(["ai.{$request->provider}.model" => $request->model]);
        }

        try {
            $service = new AiService($request->provider);
            $parsed  = $service->parse($request->raw_text, $report->test_type);

            $cases = $parsed['test_cases'] ?? [];
            if (empty($cases)) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI tidak menemukan atau gagal menyusun skenario test case dari teks yang diberikan.',
                ], 422);
            }

            $prepared = [];
            $projCode = $report->project->project_code ?? 'TC';
            $existingCount = $report->testCases()->count();

            foreach ($cases as $i => $c) {
                $num = $existingCount + $i + 1;
                $tcId = $c['test_case_id'] ?? sprintf('%s-TC-%03d', $projCode, $num);

                $prepared[] = [
                    'title'            => $c['testTitle'] ?? ($c['title'] ?? 'Test Case ' . ($i + 1)),
                    'test_case_id'     => $tcId,
                    'priority'         => in_array($c['priority'] ?? '', ['Critical', 'High', 'Medium', 'Low']) ? $c['priority'] : 'Medium',
                    'caseType'         => $c['caseType'] ?? 'Positive',
                    'precondition'     => $c['precondition'] ?? null,
                    'status'           => 'Not Executed',
                    'actual_result'    => $c['actual_result'] ?? null,
                    'notes_qa'         => $c['notes_qa'] ?? null,
                    'notes_programmer' => $c['notes_programmer'] ?? null,
                    'buckleup_ticket'  => $c['buckleup_ticket'] ?? null,
                    'bug_ticket'       => $c['bug_ticket'] ?? null,
                    'evidence_url'     => $c['evidence_url'] ?? null,
                    'is_ai_generated'  => true,
                    'review_status'    => 'needs_review',
                    'dynamic_fields'   => $this->extractDynamicFields($c, $report->test_type),
                    'steps'            => $c['steps'] ?? [],
                ];
            }

            if ($request->boolean('save_directly', false)) {
                $savedCases = [];
                DB::transaction(function () use ($report, $prepared, &$savedCases) {
                    foreach ($prepared as $pData) {
                        $tc = TestCase::create([
                            'report_id'         => $report->id,
                            'project_id'        => $report->project_id,
                            'test_type'         => $report->test_type,
                            'title'             => $pData['title'],
                            'test_case_id'      => $pData['test_case_id'],
                            'priority'          => $pData['priority'],
                            'precondition'      => $pData['precondition'],
                            'dynamic_fields'    => $pData['dynamic_fields'],
                            'status'            => 'Not Executed',
                            'actual_result'     => $pData['actual_result'],
                            'notes_qa'          => $pData['notes_qa'],
                            'notes_programmer'  => $pData['notes_programmer'],
                            'buckleup_ticket'   => $pData['buckleup_ticket'],
                            'bug_ticket'        => $pData['bug_ticket'],
                            'evidence_url'      => $pData['evidence_url'],
                            'is_ai_generated'   => true,
                            'review_status'     => 'needs_review',
                            'date_tested'       => $report->report_date ?: now(),
                            'tested_by'         => Auth::id(),
                        ]);

                        if (!empty($pData['steps']) && is_array($pData['steps'])) {
                            $stepOrder = 1;
                            foreach ($pData['steps'] as $s) {
                                $desc = is_array($s) ? ($s['step'] ?? $s['step_description'] ?? '') : (string)$s;
                                if (!empty(trim($desc))) {
                                    TestStep::create([
                                        'test_case_id'     => $tc->id,
                                        'step_order'       => $stepOrder++,
                                        'step_description' => $desc,
                                        'expected_result'  => is_array($s) ? ($s['expected'] ?? $s['expected_result'] ?? '') : '',
                                        'actual_result'    => is_array($s) ? ($s['actual'] ?? $s['actual_result'] ?? '') : '',
                                        'status'           => is_array($s) ? ($s['status'] ?? 'Not Executed') : 'Not Executed',
                                    ]);
                                }
                            }
                        }

                        $savedCases[] = $tc->load('steps');
                    }
                });

                return response()->json([
                    'success'     => true,
                    'message'     => count($savedCases) . ' draft test case berhasil di-generate AI dan disimpan ke report (Perlu Review QA).',
                    'data'        => $savedCases,
                    'qg'          => $report->fresh()->getQualityGateMetrics(),
                ]);
            }

            return response()->json([
                'success' => true,
                'data'    => $prepared,
                'count'   => count($prepared),
                'message' => 'Berhasil me-generate ' . count($prepared) . ' test case draft.',
            ]);

        } catch (\Exception $e) {
            Log::error('[ReportController::aiGenerateTestCases] ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import test cases from CSV.
     */
    public function importCsvTestCases(Request $request, $reportId)
    {
        $report = TestReport::with('project')->findOrFail($reportId);

        $request->validate([
            'csv_file' => 'required_without:csv_rows|file|mimes:csv,txt|max:5120',
            'csv_rows' => 'required_without:csv_file|array',
        ]);

        $rows = [];

        if ($request->hasFile('csv_file')) {
            $path = $request->file('csv_file')->getRealPath();
            $file = fopen($path, 'r');
            $header = null;
            while (($row = fgetcsv($file, 2000, ',')) !== false) {
                if (!$header) {
                    $header = array_map(fn($h) => strtolower(trim(str_replace([' ', '-', '_'], '', $h))), $row);
                    continue;
                }
                if (count($row) >= 1) {
                    $combined = [];
                    foreach ($header as $i => $colName) {
                        $combined[$colName] = $row[$i] ?? '';
                    }
                    $rows[] = $combined;
                }
            }
            fclose($file);
        } else {
            $rows = $request->csv_rows;
        }

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'File CSV kosong atau tidak memiliki baris data yang valid.',
            ], 422);
        }

        $imported = [];
        $projCode = $report->project->project_code ?? 'TC';
        $existingCount = $report->testCases()->count();

        DB::transaction(function () use ($report, $rows, $projCode, $existingCount, &$imported) {
            foreach ($rows as $index => $r) {
                $title = $r['scenario'] ?? $r['skenario'] ?? $r['title'] ?? $r['judul'] ?? ($r['scenario/title'] ?? ($r[1] ?? ''));
                if (empty(trim($title))) continue;

                $num = $existingCount + count($imported) + 1;
                $tcId = !empty($r['tcid']) ? $r['tcid'] : sprintf('%s-TC-%03d', $projCode, $num);
                $priority = $r['priority'] ?? $r['prioritas'] ?? 'Medium';
                if (!in_array($priority, ['Critical', 'High', 'Medium', 'Low'])) {
                    $priority = 'Medium';
                }

                $status = $r['status'] ?? 'Not Executed';
                $cleanStatus = match(strtoupper(str_replace([' ', '_', '/'], '', (string)$status))) {
                    'PASS' => 'Pass',
                    'FAIL', 'FAILED' => 'Fail',
                    'BLOCKED' => 'Blocked',
                    'NOTRUN', 'NOTEXECUTED' => 'Not Executed',
                    'NA' => 'N/A',
                    default => in_array($status, ['Not Executed', 'Not Run', 'Pass', 'Fail', 'Blocked', 'N/A']) ? $status : 'Not Executed',
                };

                $precondition = $r['precondition'] ?? $r['prasyarat'] ?? null;
                $stepText = $r['steps'] ?? $r['step'] ?? $r['langkah'] ?? '';
                $expected = $r['expectedresult'] ?? $r['expected'] ?? $r['ekspektasi'] ?? '';
                $actual = $r['actualresult'] ?? $r['actual'] ?? $r['aktual'] ?? null;

                $tc = TestCase::create([
                    'report_id'         => $report->id,
                    'project_id'        => $report->project_id,
                    'test_type'         => $report->test_type,
                    'title'             => trim($title),
                    'test_case_id'      => $tcId,
                    'priority'          => $priority,
                    'precondition'      => $precondition,
                    'dynamic_fields'    => [
                        'caseType' => $r['type'] ?? $r['tipe'] ?? 'Positive',
                    ],
                    'status'            => $cleanStatus,
                    'actual_result'     => $actual,
                    'notes_qa'          => $r['notesqa'] ?? null,
                    'notes_programmer'  => $r['notesprogrammer'] ?? null,
                    'buckleup_ticket'   => $r['buckleupticket'] ?? null,
                    'bug_ticket'        => $r['bugticket'] ?? null,
                    'evidence_url'      => $r['evidenceurl'] ?? null,
                    'is_ai_generated'   => false,
                    'review_status'     => 'reviewed',
                    'date_tested'       => $report->report_date ?: now(),
                    'tested_by'         => Auth::id(),
                ]);

                if (!empty(trim($stepText))) {
                    $lines = preg_split('/\r\n|\r|\n/', trim($stepText));
                    $order = 1;
                    foreach ($lines as $line) {
                        $cleanLine = trim(preg_replace('/^\d+[\.\)]\s*/', '', $line));
                        if (!empty($cleanLine)) {
                            TestStep::create([
                                'test_case_id'     => $tc->id,
                                'step_order'       => $order++,
                                'step_description' => $cleanLine,
                                'expected_result'  => $expected,
                                'actual_result'    => $actual,
                                'status'           => $cleanStatus,
                            ]);
                        }
                    }
                }

                $imported[] = $tc->load('steps');
            }
        });

        return response()->json([
            'success' => true,
            'message' => count($imported) . ' test case berhasil diimport dari CSV.',
            'data'    => $imported,
            'qg'      => $report->fresh()->getQualityGateMetrics(),
        ]);
    }

    /**
     * Download CSV template for test case import.
     */
    public function downloadCsvTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_test_cases.csv"',
        ];

        $columns = ['TC ID', 'Scenario', 'Type', 'Priority', 'Precondition', 'Steps', 'Expected Result', 'Status'];
        $exampleRows = [
            ['TC-001', 'Verifikasi login dengan kredensial valid', 'Positive', 'High', 'User sudah terdaftar dan status aktif', "1. Buka halaman /login\n2. Masukkan email & password\n3. Klik Masuk", 'Redirect ke Dashboard dan token tersimpan', 'Not Executed'],
            ['TC-002', 'Verifikasi login dengan password salah', 'Negative', 'Medium', 'User terdaftar di sistem', "1. Buka /login\n2. Masukkan password salah\n3. Klik Masuk", 'Tampil pesan error kredensial tidak cocok', 'Not Executed'],
            ['TC-003', 'Verifikasi proteksi SQL Injection pada input email', 'Security', 'Critical', 'Halaman login dapat diakses', "1. Masukkan payload ' OR 1=1 -- pada email\n2. Klik Masuk", 'Input ditolak dengan validasi format email', 'Not Executed'],
        ];

        $callback = function () use ($columns, $exampleRows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($exampleRows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
