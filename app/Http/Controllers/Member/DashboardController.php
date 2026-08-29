<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $membership = $user->activeMembership()->with('tier')->first();

        $stats = [
            'total_projects' => $user->projects()->count(),
            'completed_projects' => $user->projects()->where('status','completed')->count(),
            'ai_remaining' => $membership?->remainingQuota() ?? 0,
            'credit_balance' => $membership?->credit_balance ?? 0,
        ];

        $recentProjects = $user->projects()->withCount('sections')->latest()->take(6)->get();
        $tiers = \App\Models\MembershipTier::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('Member/Dashboard', [
            'stats' => $stats,
            'recentProjects' => $recentProjects,
            'tiers' => $tiers,
        ]);
    }
}
