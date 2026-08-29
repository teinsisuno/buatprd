<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipTier;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController extends Controller
{
    public function index(): Response
    {
        $tiers = MembershipTier::orderBy('sort_order')->get();
        $memberships = UserMembership::with(['user','tier'])->latest()->paginate(12);

        return Inertia::render('Admin/Memberships/Index', [
            'tiers' => $tiers,
            'memberships' => $memberships,
        ]);
    }
}
