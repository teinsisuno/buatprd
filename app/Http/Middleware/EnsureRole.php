<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // Redirect member yang nyasar ke admin
        if ($user->hasRole('member')) {
            return redirect()->route('member.dashboard')->with('error', 'Kamu tidak punya akses admin.');
        }

        abort(403, 'Tidak punya akses.');
    }
}
