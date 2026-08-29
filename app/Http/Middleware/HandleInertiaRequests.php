<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $membership = null;
        $tier = null;
        if ($user) {
            $user->loadMissing(['roles', 'activeMembership.tier']);
            $membership = $user->activeMembership;
            $tier = $membership?->tier;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->toArray(),
                    'is_superadmin' => $user->hasRole('superadmin'),
                    'is_admin' => $user->hasAnyRole(['superadmin', 'admin']),
                    'is_member' => $user->hasRole('member'),
                ] : null,
            ],
            'membership' => $membership ? [
                'id' => $membership->id,
                'status' => $membership->status,
                'tier' => $tier ? [
                    'id' => $tier->id,
                    'name' => $tier->name,
                    'slug' => $tier->slug,
                    'price' => $tier->price,
                    'limits' => $tier->limits,
                    'features' => $tier->features,
                ] : null,
                'started_at' => $membership->started_at,
                'expired_at' => $membership->expired_at,
                'ai_quota_total' => $membership->ai_quota_total,
                'ai_quota_used' => $membership->ai_quota_used,
                'ai_quota_remaining' => $membership->remainingQuota(),
                'credit_balance' => $membership->credit_balance,
                'is_expired' => $membership->isExpired(),
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
