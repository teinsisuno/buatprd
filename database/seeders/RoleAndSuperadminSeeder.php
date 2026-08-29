<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\MembershipTier;
use App\Models\UserMembership;

class RoleAndSuperadminSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage users',
            'manage staff',
            'manage memberships',
            'manage tiers',
            'manage transactions',
            'manage projects',
            'manage templates',
            'manage tickets',
            'manage settings',
            'view admin dashboard',
            'create prd',
            'export prd',
            'use ai',
            'use mermaid',
            'share prd',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $superadminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $memberRole = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        $superadminRole->syncPermissions(Permission::all());
        $adminRole->syncPermissions([
            'manage users',
            'manage memberships',
            'manage tiers',
            'manage transactions',
            'manage projects',
            'manage templates',
            'manage tickets',
            'view admin dashboard',
            'create prd',
            'export prd',
            'use ai',
            'use mermaid',
            'share prd',
        ]);
        $memberRole->syncPermissions([
            'create prd',
            'use ai',
        ]);

        // Superadmin
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@buatprd.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superadmin->syncRoles(['superadmin']);

        // Admin demo
        $admin = User::firstOrCreate(
            ['email' => 'admin@buatprd.test'],
            [
                'name' => 'Admin BuatPRD',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        // Member demo (premium)
        $member = User::firstOrCreate(
            ['email' => 'member@buatprd.test'],
            [
                'name' => 'Member Premium',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $member->syncRoles(['member']);

        // Assign memberships (ensure tiers exist)
        $defaultTier = MembershipTier::where('slug', 'default')->first();
        $premiumTier = MembershipTier::where('slug', 'premium')->first();
        $basicTier = MembershipTier::where('slug', 'basic')->first();

        if ($defaultTier && ! $superadmin->memberships()->exists()) {
            UserMembership::create([
                'user_id' => $superadmin->id,
                'tier_id' => $premiumTier ? $premiumTier->id : $defaultTier->id,
                'status' => 'active',
                'started_at' => now(),
                'expired_at' => now()->addDays(365),
                'ai_quota_total' => 9999,
                'ai_quota_used' => 0,
                'credit_balance' => 9999,
            ]);
        }
        if ($defaultTier && ! $admin->memberships()->exists()) {
            UserMembership::create([
                'user_id' => $admin->id,
                'tier_id' => $premiumTier ? $premiumTier->id : $defaultTier->id,
                'status' => 'active',
                'started_at' => now(),
                'expired_at' => now()->addDays(365),
                'ai_quota_total' => 9999,
                'ai_quota_used' => 0,
                'credit_balance' => 0,
            ]);
        }
        if ($defaultTier && ! $member->memberships()->exists()) {
            $tier = $premiumTier ?? $defaultTier;
            $limits = $tier->limits ?? [];
            UserMembership::create([
                'user_id' => $member->id,
                'tier_id' => $tier->id,
                'status' => 'active',
                'started_at' => now(),
                'expired_at' => now()->addDays(30),
                'ai_quota_total' => $limits['max_ai_per_month'] ?? 1000,
                'ai_quota_used' => 12,
                'credit_balance' => 50,
            ]);
        }
    }
}
