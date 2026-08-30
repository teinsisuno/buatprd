<?php

namespace Database\Seeders;

use App\Models\WizardPrompt;
use App\Models\WizardPromptVersion;
use Illuminate\Database\Seeder;

class WizardPromptSeeder extends Seeder
{
    public function run(): void
    {
        $prompts = config('ai.prompts', []);

        foreach ($prompts as $step => $cfg) {
            $name = $cfg['name'] ?? "Langkah $step";
            $system = $cfg['system'] ?? 'Kamu asisten PRD.';
            // user_template might be missing for placeholders 4-8 -> use short fallback
            $userTemplate = $cfg['user_template'] ?? "KONTEKS: {{title}} — {{description}}\nINPUT: {{user_message}}\nLAMPIRAN: {{attachment_text}}\nHISTORY: {{history}}\nINSTRUKSI: Output JSON sesuai kebutuhan langkah $step.";
            $jsonSchema = $cfg['json_schema'] ?? null;
            if (is_array($jsonSchema)) {
                $jsonSchema = json_encode($jsonSchema, JSON_UNESCAPED_UNICODE);
            }

            $prompt = WizardPrompt::updateOrCreate(
                ['step' => (int) $step],
                [
                    'name' => $name,
                    'system' => $system,
                    'user_template' => $userTemplate,
                    'json_schema' => $jsonSchema,
                    'is_active' => true,
                ]
            );

            // Create v1 if not exists
            if (! WizardPromptVersion::where('wizard_prompt_id', $prompt->id)->where('version', 1)->exists()) {
                WizardPromptVersion::create([
                    'wizard_prompt_id' => $prompt->id,
                    'version' => 1,
                    'name' => $name,
                    'system' => $system,
                    'user_template' => $userTemplate,
                    'json_schema' => $jsonSchema,
                    'created_by' => null,
                    'change_note' => 'Seed awal dari config/ai.php',
                ]);
            }
        }
    }
}
