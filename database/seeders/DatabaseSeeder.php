<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            MembershipTierSeeder::class,
            RoleAndSuperadminSeeder::class,
        ]);

        // Pastikan test user juga dapat default membership kalau ada
        $testUser = User::where('email', 'test@example.com')->first();
        if ($testUser && ! $testUser->memberships()->exists()) {
            $defaultTier = \App\Models\MembershipTier::where('slug', 'default')->first();
            if ($defaultTier) {
                \App\Models\UserMembership::create([
                    'user_id' => $testUser->id,
                    'tier_id' => $defaultTier->id,
                    'status' => 'active',
                    'started_at' => now(),
                    'expired_at' => now()->addDays(30),
                    'ai_quota_total' => $defaultTier->limits['max_ai_per_month'] ?? 10,
                    'ai_quota_used' => 0,
                    'credit_balance' => 0,
                ]);
                if (! $testUser->hasAnyRole(['superadmin','admin','member'])) {
                    $testUser->assignRole('member');
                }
            }
        }
    }
}
