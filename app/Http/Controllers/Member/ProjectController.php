<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $projects = $user->projects()
            ->withCount('sections')
            ->when($request->input('search'), fn($q, $s) => $q->where('title','like',"%{$s}%"))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Member/Projects/Index', [
            'projects' => $projects,
            'filters' => $request->only(['search','status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required','string','max:120'],
            'description' => ['nullable','string','max:500'],
        ]);
        $user = $request->user();
        $membership = $user->activeMembership()->with('tier')->first();
        $limit = $membership?->tier?->limits['max_projects'] ?? 2;
        if ($limit !== null && $limit < 9999) {
            $count = $user->projects()->count();
            if ($count >= $limit) {
                return back()->with('error', "Limit project tercapai ($limit). Upgrade paket untuk tambah project.");
            }
        }
        $project = Project::create([
            'user_id' => $user->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'status' => 'draft',
            'current_step' => 1,
            'progress' => 0,
        ]);
        foreach (ProjectSection::STEP_TITLES as $step => $title) {
            ProjectSection::create([
                'project_id' => $project->id,
                'step' => $step,
                'title' => $title,
                'content' => ['history'=>[],'final_output'=>null],
            ]);
        }
        return redirect()->route('member.wizard.show', [$project->id, 1])->with('success','Project dibuat. Mulai Langkah 1 — Problem & Vision.');
    }

    public function show(Request $request, Project $project): RedirectResponse
    {
        if ($project->user_id !== $request->user()->id) abort(403);
        return redirect()->route('member.wizard.show', [$project->id, $project->current_step ?? 1]);
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        if ($project->user_id !== $request->user()->id) abort(403);
        $project->delete();
        return back()->with('success','Project dihapus.');
    }
}
