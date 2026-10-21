<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the projects with pagination & search.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Projects::withCount('testCases');

        if (!empty($search)) {
            $query->search($search);
        }

        $projects = $query->latest()->paginate(10)->withQueryString();

        return view('projects', compact('projects', 'search'));
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'version' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['version'])) {
            $validated['version'] = 'v1.0.0';
        }

        Projects::create($validated);

        return redirect()->route('projects.index')
                         ->with('success', 'Project berhasil ditambahkan!');
    }

    /**
     * Display the specified project data as JSON.
     */
    public function show($id)
    {
        $project = Projects::withCount('testCases')->findOrFail($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $project,
                'stats' => $project->stats,
            ]);
        }

        return response()->json($project);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, $id)
    {
        $project = Projects::findOrFail($id);

        $validated = $request->validate([
            'project_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'version' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')
                         ->with('success', 'Project berhasil diperbarui!');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy($id)
    {
        $project = Projects::findOrFail($id);
        $project->delete();

        return redirect()->route('projects.index')
                         ->with('success', 'Project berhasil dihapus!');
    }
}
