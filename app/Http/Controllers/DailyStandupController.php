<?php

namespace App\Http\Controllers;

use App\Models\DailyStandup;
use App\Models\TestCase;
use App\Models\TestReport;
use App\Services\AiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DailyStandupController extends Controller
{
    /**
     * Display the Daily Standup AI Assistant page.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $todayDate = now()->format('Y-m-d');
        $displayDate = now()->format('d-m-Y');

        // Fetch user's QA activities for today
        $systemActivities = $this->getSystemActivitiesForDate($user->id, $todayDate);

        // Fetch recent saved daily standups
        $history = DailyStandup::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('pages.daily-standup.index', [
            'user'             => $user,
            'todayDate'        => $todayDate,
            'displayDate'      => $displayDate,
            'systemActivities' => $systemActivities,
            'history'          => $history,
        ]);
    }

    /**
     * AJAX fetch system QA activities for a specific date.
     */
    public function fetchActivity(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $userId = Auth::id();
        $activities = $this->getSystemActivitiesForDate($userId, $request->date);

        return response()->json([
            'success' => true,
            'data'    => $activities,
        ]);
    }

    /**
     * Generate Daily Standup using AI.
     */
    public function generate(Request $request)
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');

        $request->validate([
            'date'                => 'required|string',
            'user_name'           => 'required|string|max:255',
            'provider'            => 'required|in:gemini,openrouter,ollama,omniroute,openai',
            'include_system_data' => 'nullable|boolean',
            'manual_notes'        => 'nullable|string|max:10000',
            'api_key'             => 'nullable|string',
            'model'               => 'nullable|string',
            'base_url'            => 'nullable|string',
        ]);

        // Per-request overrides from UI if supplied
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
            // Format date to DD-MM-YYYY if given as YYYY-MM-DD
            $rawDate = $request->date;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                $formattedDate = Carbon::parse($rawDate)->format('d-m-Y');
                $queryDate = $rawDate;
            } else {
                $formattedDate = $rawDate;
                $queryDate = Carbon::parse($rawDate)->format('Y-m-d');
            }

            $systemActivities = ['reports' => [], 'test_cases' => []];
            if ($request->boolean('include_system_data', true)) {
                $systemActivities = $this->getSystemActivitiesForDate(Auth::id(), $queryDate);
            }

            $service = new AiService($request->provider);
            $result = $service->generateDailyStandup(
                $request->user_name,
                $formattedDate,
                $systemActivities,
                $request->input('manual_notes', '')
            );

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[DailyStandupController::generate] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store generated Daily Standup into history.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date'             => 'required|string',
            'user_name'        => 'required|string|max:255',
            'formatted_output' => 'required|string',
            'completed_tasks'  => 'nullable|string',
            'blockers'         => 'nullable|string',
            'next_plans'       => 'nullable|string',
            'raw_input'        => 'nullable|string',
        ]);

        // Normalize date to YYYY-MM-DD for database column
        $dbDate = preg_match('/^\d{2}-\d{2}-\d{4}$/', $request->date)
            ? Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d')
            : Carbon::parse($request->date)->format('Y-m-d');

        $standup = DailyStandup::create([
            'user_id'          => Auth::id(),
            'date'             => $dbDate,
            'user_name'        => $request->user_name,
            'completed_tasks'  => $request->completed_tasks,
            'blockers'         => $request->blockers ?: 'ga ada',
            'next_plans'       => $request->next_plans,
            'raw_input'        => $request->raw_input,
            'formatted_output' => $request->formatted_output,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan Daily Standup berhasil disimpan ke histori!',
            'data'    => $standup,
        ]);
    }

    /**
     * Delete a saved daily standup.
     */
    public function destroy($id)
    {
        $standup = DailyStandup::where('user_id', Auth::id())->findOrFail($id);
        $standup->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan harian berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Laporan harian berhasil dihapus.');
    }

    /**
     * Helper: Aggregate QA activities for a specific date and user.
     */
    private function getSystemActivitiesForDate(int $userId, string $dateString): array
    {
        // 1. Reports created on this date by user or general
        $reports = TestReport::with(['project', 'testCases'])
            ->where(function ($q) use ($userId, $dateString) {
                $q->where('created_by', $userId)
                  ->where(function ($dq) use ($dateString) {
                      $dq->whereDate('report_date', $dateString)
                         ->orWhereDate('created_at', $dateString);
                  });
            })
            ->get();

        $formattedReports = [];
        foreach ($reports as $rep) {
            $passCount  = $rep->pass_count;
            $failCount  = $rep->failed_count;
            $blockCount = $rep->blocked_count;

            $formattedReports[] = [
                'id'          => $rep->id,
                'name'        => $rep->report_name,
                'project'     => $rep->project->name ?? 'General Project',
                'test_type'   => ucfirst($rep->test_type),
                'total_cases' => $rep->testCases->count(),
                'pass'        => $passCount,
                'fail'        => $failCount,
                'blocked'     => $blockCount,
            ];
        }

        // 2. Test cases tested on this date
        $testCases = TestCase::with(['project', 'report'])
            ->where(function ($q) use ($userId, $dateString) {
                $q->where(function ($sub) use ($userId, $dateString) {
                    $sub->where('tested_by', $userId)
                        ->where(function ($dq) use ($dateString) {
                            $dq->whereDate('date_tested', $dateString)
                               ->orWhereDate('created_at', $dateString);
                        });
                })->orWhereHas('report', function ($rq) use ($userId, $dateString) {
                    $rq->where('created_by', $userId)
                       ->whereDate('report_date', $dateString);
                });
            })
            ->take(20)
            ->get();

        $formattedCases = [];
        foreach ($testCases as $tc) {
            $formattedCases[] = [
                'id'            => $tc->id,
                'title'         => $tc->title,
                'status'        => $tc->status,
                'actual_result' => $tc->actual_result,
                'notes_qa'      => $tc->notes_qa,
                'project'       => $tc->project->name ?? ($tc->report->project->name ?? '-'),
            ];
        }

        return [
            'reports'     => $formattedReports,
            'test_cases'  => $formattedCases,
            'total_items' => count($formattedReports) + count($formattedCases),
        ];
    }
}
