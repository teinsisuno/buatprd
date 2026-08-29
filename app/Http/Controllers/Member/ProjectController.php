<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
}
