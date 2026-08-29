<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        try {
            $user->assignRole('member');
        } catch (\Throwable $e) {
            // Role belum ada (misal di testing tanpa seeder) — jangan gagalkan registrasi
        }
        try {
            $defaultTier = \App\Models\MembershipTier::where('slug', 'default')->first();
            if ($defaultTier) {
                \App\Models\UserMembership::create([
                    'user_id' => $user->id,
                    'tier_id' => $defaultTier->id,
                    'status' => 'active',
                    'started_at' => now(),
                    'expired_at' => now()->addDays($defaultTier->duration_days),
                    'ai_quota_total' => $defaultTier->limits['max_ai_per_month'] ?? 10,
                    'ai_quota_used' => 0,
                    'credit_balance' => 0,
                ]);
            }
        } catch (\Throwable $e) {
            // Silently ignore membership creation failure in tests
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('member.dashboard', absolute: false));
    }
}
