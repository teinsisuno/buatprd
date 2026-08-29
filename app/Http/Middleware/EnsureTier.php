<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTier
{
    public function handle(Request $request, Closure $next, string $tierSlug, string $feature = null): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Harus login.');
        }

        $membership = $user->activeMembership;
        if (! $membership) {
            return redirect()->route('member.billing.index')->with('error', 'Tier tidak ditemukan. Silakan pilih paket.');
        }

        $limits = $membership->tier->limits ?? [];
        $hierarchy = ['default' => 0, 'basic' => 1, 'standart' => 2, 'premium' => 3];
        $userLevel = $hierarchy[$membership->tier->slug] ?? 0;
        $requiredLevel = $hierarchy[$tierSlug] ?? 0;

        if ($userLevel < $requiredLevel) {
            if ($feature) {
                $featureLabels = [
                    'export_pdf' => 'Export PDF',
                    'mermaid' => 'Diagram Mermaid',
                    'share' => 'Share ke Tim',
                    'template_premium' => 'Template Premium',
                ];
                $label = $featureLabels[$feature] ?? $feature;
                return redirect()->route('member.billing.index')->with('error', "Fitur {$label} butuh paket {$tierSlug} ke atas. Tier kamu: {$membership->tier->name}.");
            }
            return redirect()->route('member.billing.index')->with('error', "Butuh paket {$tierSlug} ke atas. Tier kamu: {$membership->tier->name}.");
        }

        // Feature-specific check
        if ($feature && isset($limits[$feature]) && $limits[$feature] === false) {
            return redirect()->route('member.billing.index')->with('error', "Fitur ini tidak tersedia di paket {$membership->tier->name}. Upgrade ke {$tierSlug}.");
        }

        return $next($request);
    }
}
