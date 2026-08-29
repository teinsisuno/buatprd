<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MembershipTier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with(['roles','activeMembership.tier']);

        if ($search = $request->input('search')) {
            $query->where(fn($q) => $q->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%"));
        }
        if ($role = $request->input('role')) {
            $query->role($role);
        }
        if ($tier = $request->input('tier')) {
            $query->whereHas('activeMembership.tier', fn($q) => $q->where('slug', $tier));
        }

        $users = $query->latest()->paginate(12)->withQueryString();
        $tiers = MembershipTier::orderBy('sort_order')->get(['id','name','slug']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'tiers' => $tiers,
            'filters' => $request->only(['search','role','tier']),
        ]);
    }
}
