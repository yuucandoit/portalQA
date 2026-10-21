<?php

namespace App\Http\Controllers;

use App\Models\Bug;
use App\Models\Projects;
use App\Models\TestCase;
use App\Models\TestReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BugController extends Controller
{
    /**
     * Display a listing of the bugs with Summary_Bug Matrix (QA-TPL-001).
     */
    public function index(Request $request)
    {
        $query = Bug::with(['project', 'testCase', 'report'])->latest('found_date');

        // Filter: Project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: Severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter: Priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter: Bug Type
        if ($request->filled('bug_type')) {
            $query->where('bug_type', $request->bug_type);
        }

        // Search text
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('bug_id', 'LIKE', "%{$s}%")
                  ->orWhere('title', 'LIKE', "%{$s}%")
                  ->orWhere('related_test_case_id', 'LIKE', "%{$s}%")
                  ->orWhere('module_feature', 'LIKE', "%{$s}%")
                  ->orWhere('reported_by', 'LIKE', "%{$s}%")
                  ->orWhere('assigned_to_dev', 'LIKE', "%{$s}%");
            });
        }

        $bugs = $query->paginate(15)->withQueryString();

        // Projects for filter
        $projects = Projects::orderBy('name')->get();

        // Calculate QA-TPL-001 Summary_Bug Metrics & Matrix
        $baseQuery = Bug::query();
        if ($request->filled('project_id')) {
            $baseQuery->where('project_id', $request->project_id);
        }

        $totalBugCount = (clone $baseQuery)->count();
        $activeBugCount = (clone $baseQuery)->whereIn('status', ['Open', 'In Progress', 'Fixed', 'Ready to Retest', 'Reopen'])->count();
        $critMajorActiveCount = (clone $baseQuery)->whereIn('status', ['Open', 'In Progress', 'Fixed', 'Ready to Retest', 'Reopen'])
            ->whereIn('severity', ['Critical', 'Major'])->count();
        $closedBugCount = (clone $baseQuery)->where('status', 'Closed')->count();
        $reopenCount = (clone $baseQuery)->where('status', 'Reopen')->count();
        $rejectedCount = (clone $baseQuery)->where('status', 'Rejected')->count();
        $noTcLinkCount = (clone $baseQuery)->whereNull('related_test_case_id')->orWhere('related_test_case_id', '')->count();

        $reopenRate = $totalBugCount > 0 ? round(($reopenCount / $totalBugCount) * 100, 1) : 0;
        $rejectedRate = $totalBugCount > 0 ? round(($rejectedCount / $totalBugCount) * 100, 1) : 0;

        // Matrix Severity x Status
        $matrixRaw = (clone $baseQuery)
            ->selectRaw('severity, status, count(*) as total')
            ->groupBy('severity', 'status')
            ->get();

        $matrix = [];
        $severities = array_keys(Bug::SEVERITIES);
        $statuses = Bug::STATUSES;

        foreach ($severities as $sev) {
            foreach ($statuses as $st) {
                $matrix[$sev][$st] = 0;
            }
            $matrix[$sev]['total'] = 0;
        }

        foreach ($matrixRaw as $row) {
            if (isset($matrix[$row->severity][$row->status])) {
                $matrix[$row->severity][$row->status] = $row->total;
                $matrix[$row->severity]['total'] += $row->total;
            }
        }

        $statusTotals = [];
        foreach ($statuses as $st) {
            $statusTotals[$st] = 0;
            foreach ($severities as $sev) {
                $statusTotals[$st] += $matrix[$sev][$st];
            }
        }

        $kpis = [
            'total'             => $totalBugCount,
            'active'            => $activeBugCount,
            'critical_major'    => $critMajorActiveCount,
            'closed'            => $closedBugCount,
            'reopen_rate'       => $reopenRate,
            'rejected_rate'     => $rejectedRate,
            'no_tc_link'        => $noTcLinkCount,
        ];

        return view('pages.bugs.index', compact(
            'bugs',
            'projects',
            'kpis',
            'matrix',
            'statuses',
            'severities',
            'statusTotals'
        ));
    }

    /**
     * Show the form for creating a new bug.
     */
    public function create(Request $request)
    {
        $projects = Projects::orderBy('name')->get();
        $defaultBugId = Bug::generateNextBugId();
        
        $prefill = [
            'project_id'            => $request->query('project_id', ''),
            'report_id'             => $request->query('report_id', ''),
            'test_case_id'          => $request->query('test_case_id', ''),
            'related_test_case_id'  => $request->query('related_test_case_id', ''),
            'module_feature'        => $request->query('module_feature', ''),
            'title'                 => $request->query('title', ''),
            'steps_to_reproduce'    => $request->query('steps_to_reproduce', ''),
            'expected_result'       => $request->query('expected_result', ''),
            'actual_result'         => $request->query('actual_result', ''),
            'evidence_url'          => $request->query('evidence_url', ''),
        ];

        return view('pages.bugs.create', compact('projects', 'defaultBugId', 'prefill'));
    }

    /**
     * Store a newly created bug.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id'            => 'required|exists:projects,id',
            'bug_id'                => 'required|string|max:50',
            'found_date'            => 'required|date',
            'module_feature'        => 'nullable|string|max:255',
            'bug_type'              => 'required|string|max:50',
            'title'                 => 'required|string|max:255',
            'steps_to_reproduce'    => 'nullable|string',
            'expected_result'       => 'nullable|string',
            'actual_result'         => 'nullable|string',
            'severity'              => 'required|string|max:50',
            'priority'              => 'required|string|max:50',
            'environment'           => 'required|string|max:50',
            'device_environment'    => 'nullable|string|max:255',
            'evidence_url'          => 'nullable|string',
            'related_test_case_id'  => 'nullable|string|max:100',
            'assigned_to_dev'       => 'nullable|string|max:100',
            'status'                => 'required|string|max:50',
            'build_version_found'   => 'nullable|string|max:100',
            'report_id'             => 'nullable|exists:test_reports,id',
            'test_case_id'          => 'nullable|exists:test_cases,id',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['reported_by'] = Auth::user()->name ?? 'QA Tester';

        // Auto date logic for Fixed
        if ($validated['status'] === 'Fixed' && !$request->filled('fixed_date')) {
            $validated['fixed_date'] = Carbon::today()->toDateString();
        }

        $bug = Bug::create($validated);

        // Bi-directional link: update related TestCase if present
        if (!empty($validated['related_test_case_id'])) {
            TestCase::where('test_case_id', $validated['related_test_case_id'])
                ->update(['bug_ticket' => $bug->bug_id]);
        }
        if (!empty($validated['test_case_id'])) {
            TestCase::where('id', $validated['test_case_id'])
                ->update(['bug_ticket' => $bug->bug_id]);
        }

        return redirect()->route('bugs.index')->with('success', "Bug [{$bug->bug_id}] berhasil dicatat!");
    }

    /**
     * Display the specified bug details.
     */
    public function show(Bug $bug)
    {
        $bug->load(['project', 'testCase', 'report', 'user']);
        return view('pages.bugs.show', compact('bug'));
    }

    /**
     * Show the form for editing the bug.
     */
    public function edit(Bug $bug)
    {
        $projects = Projects::orderBy('name')->get();
        return view('pages.bugs.edit', compact('bug', 'projects'));
    }

    /**
     * Update the specified bug in storage.
     */
    public function update(Request $request, Bug $bug)
    {
        $validated = $request->validate([
            'project_id'            => 'required|exists:projects,id',
            'bug_id'                => 'required|string|max:50',
            'found_date'            => 'required|date',
            'module_feature'        => 'nullable|string|max:255',
            'bug_type'              => 'required|string|max:50',
            'title'                 => 'required|string|max:255',
            'steps_to_reproduce'    => 'nullable|string',
            'expected_result'       => 'nullable|string',
            'actual_result'         => 'nullable|string',
            'severity'              => 'required|string|max:50',
            'priority'              => 'required|string|max:50',
            'environment'           => 'required|string|max:50',
            'device_environment'    => 'nullable|string|max:255',
            'evidence_url'          => 'nullable|string',
            'related_test_case_id'  => 'nullable|string|max:100',
            'assigned_to_dev'       => 'nullable|string|max:100',
            'status'                => 'required|string|max:50',
            'fixed_date'            => 'nullable|date',
            'dev_notes'             => 'nullable|string',
            'retest_date'           => 'nullable|date',
            'retested_by'           => 'nullable|string|max:100',
            'qa_retest_notes'       => 'nullable|string',
            'build_version_found'   => 'nullable|string|max:100',
            'build_version_fix'     => 'nullable|string|max:100',
        ]);

        // Lifecycle timestamp helpers
        if ($validated['status'] === 'Fixed' && empty($validated['fixed_date'])) {
            $validated['fixed_date'] = Carbon::today()->toDateString();
        }

        if (in_array($validated['status'], ['Ready to Retest', 'Closed', 'Reopen'])) {
            if (empty($validated['retest_date'])) {
                $validated['retest_date'] = Carbon::today()->toDateString();
            }
            if (empty($validated['retested_by'])) {
                $validated['retested_by'] = Auth::user()->name ?? 'QA Tester';
            }
        }

        $bug->update($validated);

        // Bi-directional link check
        if (!empty($validated['related_test_case_id'])) {
            TestCase::where('test_case_id', $validated['related_test_case_id'])
                ->update(['bug_ticket' => $bug->bug_id]);
        }

        return redirect()->route('bugs.index')->with('success', "Bug [{$bug->bug_id}] berhasil diperbarui!");
    }

    /**
     * Remove the specified bug from storage.
     */
    public function destroy(Bug $bug)
    {
        $id = $bug->bug_id;
        $bug->delete();

        return redirect()->route('bugs.index')->with('success', "Bug [{$id}] berhasil dihapus!");
    }

    /**
     * Quick Store Bug via AJAX (Invoked when QA marks Fail on Test Case form)
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'project_id'            => 'nullable|exists:projects,id',
            'report_id'             => 'nullable|exists:test_reports,id',
            'related_test_case_id'  => 'nullable|string|max:100',
            'title'                 => 'required|string|max:255',
            'module_feature'        => 'nullable|string|max:255',
            'steps_to_reproduce'    => 'nullable|string',
            'actual_result'         => 'nullable|string',
            'expected_result'       => 'nullable|string',
            'severity'              => 'nullable|string|max:50',
            'priority'              => 'nullable|string|max:50',
            'evidence_url'          => 'nullable|string',
        ]);

        $bugId = Bug::generateNextBugId();

        $bug = Bug::create([
            'project_id'            => $validated['project_id'] ?? null,
            'report_id'             => $validated['report_id'] ?? null,
            'bug_id'                => $bugId,
            'found_date'            => Carbon::today()->toDateString(),
            'module_feature'        => $validated['module_feature'] ?? 'General',
            'bug_type'              => 'Functional',
            'title'                 => $validated['title'],
            'steps_to_reproduce'    => $validated['steps_to_reproduce'] ?? null,
            'expected_result'       => $validated['expected_result'] ?? null,
            'actual_result'         => $validated['actual_result'] ?? null,
            'severity'              => $validated['severity'] ?? 'Major',
            'priority'              => $validated['priority'] ?? 'P2 - High',
            'environment'           => 'SIT',
            'evidence_url'          => $validated['evidence_url'] ?? null,
            'related_test_case_id'  => $validated['related_test_case_id'] ?? null,
            'reported_by'           => Auth::user()->name ?? 'QA Tester',
            'user_id'               => Auth::id(),
            'status'                => 'Open',
        ]);

        return response()->json([
            'success' => true,
            'bug_id'  => $bug->bug_id,
            'bug'     => $bug,
            'message' => "Bug {$bug->bug_id} berhasil dibuat otomatis!",
        ]);
    }
}
