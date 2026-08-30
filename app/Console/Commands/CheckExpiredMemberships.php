<?php

namespace App\Console\Commands;

use App\Models\UserMembership;
use App\Models\MembershipTier;
use Illuminate\Console\Command;

class CheckExpiredMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired memberships and downgrade to default tier (with 3-day grace period)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $graceDays = 3;

        // 1. Mark expired memberships (past expired_at) as 'expired' status
        $expired = UserMembership::where('status', 'active')
            ->where('expired_at', '<', $now)
            ->get();

        $this->info("Found {$expired->count()} expired memberships.");

        $defaultTier = MembershipTier::where('slug', 'default')->first();
        if (!$defaultTier) {
            $this->error('Default tier not found! Cannot downgrade.');
            return 1;
        }

        foreach ($expired as $membership) {
            $expiredAt = $membership->expired_at;
            $graceEnd = $expiredAt->copy()->addDays($graceDays);

            if ($now->greaterThan($graceEnd)) {
                // Grace period ended — downgrade to default
                $membership->update(['status' => 'expired']);

                // Create new default membership
                UserMembership::create([
                    'user_id' => $membership->user_id,
                    'tier_id' => $defaultTier->id,
                    'status' => 'active',
                    'started_at' => $now,
                    'expired_at' => $now->copy()->addDays(30),
                    'ai_quota_total' => $defaultTier->limits['max_ai_per_month'] ?? 10,
                    'ai_quota_used' => 0,
                    'credit_balance' => $membership->credit_balance, // keep credits
                ]);

                activity()
                    ->performedOn($membership)
                    ->log("Membership expired & downgraded to default (user #{$membership->user_id})");

                $this->line("  Downgraded user #{$membership->user_id} → default (grace ended)");
            } else {
                // Still in grace period — mark as 'grace'
                $membership->update(['status' => 'grace']);
                $this->line("  User #{$membership->user_id} in grace period (ends {$graceEnd->format('Y-m-d')})");
            }
        }

        // 2. Count stats
        $activeCount = UserMembership::where('status', 'active')->count();
        $graceCount = UserMembership::where('status', 'grace')->count();
        $expiredCount = UserMembership::where('status', 'expired')->count();

        $this->info("Stats: {$activeCount} active, {$graceCount} grace, {$expiredCount} expired");

        return 0;
    }
}
