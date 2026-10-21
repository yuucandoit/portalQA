<?php

namespace App\Http\Controllers;

use App\Models\CopilotChat;
use App\Models\CopilotMessage;
use App\Models\Projects;
use App\Models\TestCase;
use App\Models\TestReport;
use App\Models\TestStep;
use App\Services\CopilotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CopilotController extends Controller
{
    private CopilotService $copilotService;

    public function __construct(CopilotService $copilotService)
    {
        $this->copilotService = $copilotService;
    }

    /**
     * Display the 3-Pane AI Copilot Studio workspace.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. All projects for context selector
        $projects = Projects::orderBy('name')->get();

        // Active project from query or first project
        $activeProjectId = $request->input('project_id', $projects->first()?->id);

        // 2. Reports for active project
        $reports = $activeProjectId
            ? TestReport::where('project_id', $activeProjectId)->latest()->get()
            : collect();

        $activeReportId = $request->input('report_id');

        // 3. User chat sessions history
        $chats = CopilotChat::where('user_id', $user->id)
            ->withCount('messages')
            ->latest()
            ->get();

        // Active chat session
        $activeChat = null;
        $messages   = collect();
        $chatId     = $request->input('chat_id');

        if ($chatId) {
            $activeChat = CopilotChat::where('user_id', $user->id)->find($chatId);
        }

        if (!$activeChat && $chats->isNotEmpty()) {
            $activeChat = $chats->first();
        }

        if ($activeChat) {
            $messages = $activeChat->messages()->get();
            if ($activeChat->project_id && !$request->filled('project_id')) {
                $activeProjectId = $activeChat->project_id;
                $reports = TestReport::where('project_id', $activeProjectId)->latest()->get();
            }
            if ($activeChat->report_id && !$request->filled('report_id')) {
                $activeReportId = $activeChat->report_id;
            }
        }

        return view('pages.copilot.index', compact(
            'projects',
            'reports',
            'chats',
            'activeChat',
            'messages',
            'activeProjectId',
            'activeReportId'
        ));
    }

    /**
     * Get reports for a specific project (AJAX for dropdown switch).
     */
    public function getReportsByProject($projectId)
    {
        $reports = TestReport::where('project_id', $projectId)
            ->select('id', 'report_name', 'test_type', 'sprint', 'cycle')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }

    /**
     * Create a new chat session.
     */
    public function createChat(Request $request)
    {
        $user = Auth::user();

        $chat = CopilotChat::create([
            'user_id'    => $user->id,
            'project_id' => $request->project_id ?: null,
            'report_id'  => $request->report_id ?: null,
            'title'      => 'Percakapan Baru',
        ]);

        return response()->json([
            'success' => true,
            'chat'    => $chat,
        ]);
    }

    /**
     * Load messages and last draft for a chat session.
     */
    public function loadChat($id)
    {
        $user = Auth::user();
        $chat = CopilotChat::where('user_id', $user->id)->with('messages')->findOrFail($id);

        // Find the most recent draft payload across assistant messages
        $lastDraft = null;
        foreach ($chat->messages->reverse() as $msg) {
            if (!empty($msg->draft_payload['test_cases'])) {
                $lastDraft = $msg->draft_payload;
                break;
            }
        }

        return response()->json([
            'success'    => true,
            'chat'       => $chat,
            'messages'   => $chat->messages,
            'last_draft' => $lastDraft,
        ]);
    }

    /**
     * Delete a chat session.
     */
    public function deleteChat($id)
    {
        $user = Auth::user();
        $chat = CopilotChat::where('user_id', $user->id)->findOrFail($id);
        $chat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesi percakapan berhasil dihapus.',
        ]);
    }

    /**
     * Send message and get AI response with draft payload.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message'        => 'required|string|max:10000',
            'chat_id'        => 'nullable|exists:copilot_chats,id',
            'project_id'     => 'nullable|exists:projects,id',
            'report_id'      => 'nullable|exists:test_reports,id',
            'current_drafts' => 'nullable|array',
            'provider'       => 'nullable|string',
        ]);

        $user = Auth::user();
        $chat = null;

        if ($request->filled('chat_id')) {
            $chat = CopilotChat::where('user_id', $user->id)->find($request->chat_id);
        }

        if (!$chat) {
            $firstWords = mb_substr(trim($request->message), 0, 35);
            $chat = CopilotChat::create([
                'user_id'    => $user->id,
                'project_id' => $request->project_id ?: null,
                'report_id'  => $request->report_id ?: null,
                'title'      => $firstWords ?: 'Percakapan Baru',
            ]);
        } else {
            // Update context if changed
            $chat->update([
                'project_id' => $request->project_id ?: $chat->project_id,
                'report_id'  => $request->report_id ?: $chat->report_id,
            ]);
        }

        // 1. Save user message
        CopilotMessage::create([
            'chat_id' => $chat->id,
            'role'    => 'user',
            'content' => $request->message,
        ]);

        // 2. Ask Copilot Service with Auto-Fallback
        try {
            $preferredProvider = $request->input('provider', 'gemini');
            $currentDrafts     = $request->input('current_drafts', []);

            $aiResult = $this->copilotService->ask(
                $request->message,
                $request->project_id ? (int)$request->project_id : null,
                $request->report_id ? (int)$request->report_id : null,
                $currentDrafts,
                $preferredProvider
            );

            // 3. Save assistant message with draft payload
            $assistantMsg = CopilotMessage::create([
                'chat_id'       => $chat->id,
                'role'          => 'assistant',
                'content'       => $aiResult['response_text'],
                'draft_payload' => $aiResult['payload'],
                'action_type'   => $aiResult['payload']['action'] ?? 'query_reply',
                'provider_used' => $aiResult['provider_used'],
            ]);

            // Auto-rename chat if it's the second message and title is default
            if ($chat->title === 'Percakapan Baru' || mb_strlen($chat->title) < 5) {
                $autoTitle = mb_substr(trim($request->message), 0, 40);
                $chat->update(['title' => $autoTitle]);
            }

            return response()->json([
                'success'       => true,
                'chat_id'       => $chat->id,
                'chat_title'    => $chat->title,
                'message_id'    => $assistantMsg->id,
                'response_text' => $aiResult['response_text'],
                'payload'       => $aiResult['payload'],
                'provider_used' => $aiResult['provider_used'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[CopilotController::sendMessage] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Commit selected draft test cases directly into database (test_cases & test_steps).
     */
    public function saveDraftToReport(Request $request)
    {
        $request->validate([
            'save_mode'                 => 'required|in:existing,new',
            'project_id'                => 'required|exists:projects,id',
            'report_id'                 => 'nullable|required_if:save_mode,existing|exists:test_reports,id',
            'report_name'               => 'nullable|required_if:save_mode,new|string|max:255',
            'test_type'                 => 'nullable|required_if:save_mode,new|string|in:functional,integration,regression,performance,reliability,stress,negative,validation,hardware',
            'sprint'                    => 'nullable|string|max:50',
            'cycle'                     => 'nullable|string|max:50',
            'report_date'               => 'nullable|date',
            'test_cases'                => 'required|array|min:1',
            'test_cases.*.testTitle'    => 'required|string|max:255',
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($request, $user) {
            $report = null;

            if ($request->save_mode === 'new') {
                $report = TestReport::create([
                    'project_id'  => $request->project_id,
                    'created_by'  => $user->id,
                    'report_name' => $request->report_name,
                    'sprint'      => $request->sprint ?: null,
                    'cycle'       => $request->cycle ?: 'Siklus 1',
                    'test_type'   => $request->test_type ?: 'functional',
                    'report_date' => $request->report_date ?: now()->format('Y-m-d'),
                    'description' => 'Dibuat via Antigravity QA Copilot Studio',
                ]);
            } else {
                $report = TestReport::findOrFail($request->report_id);
            }

            $savedCount = 0;
            $testType = $report->test_type;

            foreach ($request->test_cases as $caseData) {
                // Determine clean status
                $rawStatus = $caseData['status'] ?? 'Not Run';
                $cleanStatus = match(strtoupper(str_replace([' ', '_', '/'], '', $rawStatus))) {
                    'PASS'           => 'Pass',
                    'FAIL', 'FAILED' => 'Fail',
                    'BLOCKED'        => 'Blocked',
                    'NOTRUN'         => 'Not Run',
                    'NA'             => 'N/A',
                    default          => in_array($rawStatus, ['Not Run', 'Pass', 'Fail', 'Blocked', 'N/A']) ? $rawStatus : 'Not Run',
                };

                // Build dynamic fields per QA-TPL-001
                $dynamicFields = [
                    'caseType'           => $caseData['caseType'] ?? 'Positive',
                    'priority'           => $caseData['priority'] ?? 'Medium',
                    'moduleFeature'      => $caseData['moduleFeature'] ?? 'General',
                    'feature'            => $caseData['feature'] ?? '',
                    'refRequirement'     => $caseData['refRequirement'] ?? '',
                    'testData'           => $caseData['testData'] ?? '',
                    'componentSubsystem' => $caseData['componentSubsystem'] ?? '',
                    'parameterMeasured'  => $caseData['parameterMeasured'] ?? '',
                    'unit'               => $caseData['unit'] ?? '',
                    'passLimitSpec'      => $caseData['passLimitSpec'] ?? '',
                    'specSource'         => $caseData['specSource'] ?? '',
                    'measuredValue'      => $caseData['measuredValue'] ?? '',
                    'deviceTested'       => $caseData['deviceTested'] ?? '',
                    'environmentSpec'    => $caseData['environmentSpec'] ?? '',
                ];

                $testCase = TestCase::create([
                    'report_id'        => $report->id,
                    'project_id'       => $report->project_id,
                    'test_type'        => $testType,
                    'title'            => $caseData['testTitle'],
                    'test_case_id'     => $caseData['testCaseId'] ?? null,
                    'priority'         => $caseData['priority'] ?? 'Medium',
                    'precondition'     => $caseData['precondition'] ?? null,
                    'dynamic_fields'   => $dynamicFields,
                    'status'           => $cleanStatus,
                    'actual_result'    => $caseData['actualResult'] ?? null,
                    'notes_qa'         => $caseData['notesQA'] ?? null,
                    'notes_programmer' => $caseData['notesProgrammer'] ?? null,
                    'buckleup_ticket'  => $caseData['buckleupTicket'] ?? null,
                    'bug_ticket'       => $caseData['bugTicket'] ?? null,
                    'evidence_url'     => $caseData['evidenceUrl'] ?? null,
                    'date_tested'      => $report->report_date ?: now(),
                    'tested_by'        => $user->id,
                ]);

                // Create test steps
                if (!empty($caseData['steps']) && is_array($caseData['steps'])) {
                    $order = 1;
                    foreach ($caseData['steps'] as $s) {
                        $desc = is_array($s) ? ($s['step'] ?? '') : (string)$s;
                        $exp  = is_array($s) ? ($s['expected'] ?? '') : '';
                        if (!empty(trim($desc))) {
                            TestStep::create([
                                'test_case_id'     => $testCase->id,
                                'step_order'       => $order++,
                                'step_description' => $desc,
                                'expected_result'  => $exp,
                            ]);
                        }
                    }
                }

                $savedCount++;
            }

            return response()->json([
                'success'     => true,
                'message'     => "Berhasil menyimpan {$savedCount} test case ke report \"{$report->report_name}\"!",
                'report_id'   => $report->id,
                'report_name' => $report->report_name,
                'report_url'  => route('reports.show', $report->id),
            ]);
        });
    }
}
