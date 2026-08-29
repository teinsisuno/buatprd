<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MembershipTier;

class MembershipTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Default',
                'slug' => 'default',
                'price' => 0,
                'duration_days' => 30,
                'limits' => [
                    'max_projects' => 2,
                    'max_ai_per_month' => 10,
                    'can_export_pdf' => false,
                    'can_mermaid' => false,
                    'can_share' => false,
                    'can_template_premium' => false,
                ],
                'features' => ['2 Project', '10x Generate AI / bulan', 'Wizard 8 Langkah', 'Tanpa Export PDF'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price' => 49000,
                'duration_days' => 30,
                'limits' => [
                    'max_projects' => 10,
                    'max_ai_per_month' => 100,
                    'can_export_pdf' => true,
                    'can_mermaid' => false,
                    'can_share' => false,
                    'can_template_premium' => false,
                ],
                'features' => ['10 Project', '100x Generate AI', 'Export PDF & Markdown', 'Template Dasar'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Standart',
                'slug' => 'standart',
                'price' => 99000,
                'duration_days' => 30,
                'limits' => [
                    'max_projects' => 30,
                    'max_ai_per_month' => 300,
                    'can_export_pdf' => true,
                    'can_mermaid' => true,
                    'can_share' => true,
                    'can_template_premium' => true,
                ],
                'features' => ['30 Project', '300x Generate AI', 'Diagram Mermaid & DBML', 'Hapus Watermark', 'Share ke Tim'],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 199000,
                'duration_days' => 30,
                'limits' => [
                    'max_projects' => 9999,
                    'max_ai_per_month' => 1000,
                    'can_export_pdf' => true,
                    'can_mermaid' => true,
                    'can_share' => true,
                    'can_template_premium' => true,
                ],
                'features' => ['Unlimited Project', '1000x Generate AI', 'Prioritas Support', 'Akses Template Premium', 'Fitur Tim'],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($tiers as $tier) {
            MembershipTier::updateOrCreate(['slug' => $tier['slug']], $tier);
        }
    }
}
