<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\MembershipTier;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_users' => User::count(),
            'total_members' => User::role('member')->count(),
            'total_projects' => Project::count(),
            'pending_transactions' => Transaction::where('status', 'pending')->count(),
            'revenue_this_month' => Transaction::where('status', 'approved')
                ->whereMonth('approved_at', now()->month)
                ->whereYear('approved_at', now()->year)
                ->sum('amount'),
        ];

        $tierDistribution = MembershipTier::withCount(['memberships' => fn($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')->get(['id','name','slug','price']);

        $recentTransactions = Transaction::with(['user','tier'])->latest()->take(5)->get();
        $recentUsers = User::with('activeMembership.tier')->latest()->take(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'tierDistribution' => $tierDistribution,
            'recentTransactions' => $recentTransactions,
            'recentUsers' => $recentUsers,
        ]);
    }
}
