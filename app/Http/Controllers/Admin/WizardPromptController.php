<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WizardPrompt;
use App\Models\WizardPromptVersion;
use App\Services\AiService;
use App\Services\PromptResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WizardPromptController extends Controller
{
    public function index(): Response
    {
        $prompts = WizardPrompt::orderBy('step')->get();
        $cfgPrompts = config('ai.prompts', []);

        // Ensure we show 8 steps even if DB row missing -> synthesize from config
        $rows = [];
        for ($i = 1; $i <= 8; $i++) {
            $db = $prompts->firstWhere('step', $i);
            $cfg = $cfgPrompts[$i] ?? null;
            $versionCount = $db ? WizardPromptVersion::where('wizard_prompt_id', $db->id)->count() : 0;

            if ($db) {
                $rows[] = [
                    'step' => $db->step,
                    'name' => $db->name,
                    'system' => $db->system,
                    'user_template' => $db->user_template,
                    'json_schema' => $db->json_schema,
                    'is_active' => $db->is_active,
                    'source' => $db->is_active ? 'db' : 'config (nonaktif)',
                    'updated_at' => $db->updated_at?->toDateTimeString(),
                    'updated_by' => $db->updated_by,
                    'version_count' => $versionCount,
                    'exists_in_db' => true,
                    'id' => $db->id,
                ];
            } else {
                $rows[] = [
                    'step' => $i,
                    'name' => $cfg['name'] ?? "Langkah $i",
                    'system' => $cfg['system'] ?? '—',
                    'user_template' => $cfg['user_template'] ?? '—',
                    'json_schema' => $cfg['json_schema'] ?? null,
                    'is_active' => false,
                    'source' => 'config (belum di DB)',
                    'updated_at' => null,
                    'updated_by' => null,
                    'version_count' => 0,
                    'exists_in_db' => false,
                    'id' => null,
                ];
            }
        }

        $hasEmpty = $prompts->count() === 0;

        return Inertia::render('Admin/WizardPrompts/Index', [
            'prompts' => $rows,
            'hasEmpty' => $hasEmpty,
            'allowedVars' => PromptResolver::ALLOWED_VARS,
        ]);
    }

    public function show(int $step)
    {
        $this->validateStep($step);
        $data = PromptResolver::get($step);
        $db = WizardPrompt::where('step', $step)->first();
        $versions = $db ? WizardPromptVersion::where('wizard_prompt_id', $db->id)->orderByDesc('version')->limit(5)->get() : [];

        return response()->json([
            'step' => $step,
            'prompt' => $data,
            'db' => $db,
            'recent_versions' => $versions,
            'allowed_vars' => PromptResolver::ALLOWED_VARS,
            'dummy_preview' => PromptResolver::render(
                $data['user_template'] ?? '',
                PromptResolver::dummyVars($step)
            ),
        ]);
    }

    public function update(Request $request, int $step)
    {
        $this->validateStep($step);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'system' => ['required', 'string', 'min:20', 'max:8000'],
            'user_template' => ['required', 'string', 'min:20', 'max:12000'],
            'json_schema' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // json_schema must be valid JSON if present
        if (!empty($data['json_schema'])) {
            $decoded = json_decode($data['json_schema'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['json_schema' => 'JSON tidak valid: ' . json_last_error_msg()])->withInput();
            }
            // Normalize pretty
            $data['json_schema'] = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            $data['json_schema'] = null;
        }

        // Check unknown vars in system + user_template
        $unknownSystem = PromptResolver::unknownVars($data['system']);
        $unknownTemplate = PromptResolver::unknownVars($data['user_template']);
        $unknown = array_values(array_unique(array_merge($unknownSystem, $unknownTemplate)));
        if (!empty($unknown)) {
            $list = implode(', ', array_map(fn($v) => '{{'.$v.'}}', $unknown));
            $allowed = implode(', ', PromptResolver::ALLOWED_VARS);
            return back()->withErrors(['user_template' => "Variabel tidak dikenal: $list. Pilih dari: $allowed"])->withInput();
        }

        $prompt = WizardPrompt::where('step', $step)->first();

        DB::transaction(function () use ($step, $data, &$prompt) {
            if (! $prompt) {
                $prompt = WizardPrompt::create([
                    'step' => $step,
                    'name' => $data['name'],
                    'system' => $data['system'],
                    'user_template' => $data['user_template'],
                    'json_schema' => $data['json_schema'],
                    'is_active' => $data['is_active'] ?? true,
                    'updated_by' => auth()->id(),
                ]);
            } else {
                $prompt->update([
                    'name' => $data['name'],
                    'system' => $data['system'],
                    'user_template' => $data['user_template'],
                    'json_schema' => $data['json_schema'],
                    'is_active' => $data['is_active'] ?? $prompt->is_active,
                    'updated_by' => auth()->id(),
                ]);
            }

            // Create version snapshot
            $maxVersion = WizardPromptVersion::where('wizard_prompt_id', $prompt->id)->max('version') ?? 0;
            WizardPromptVersion::create([
                'wizard_prompt_id' => $prompt->id,
                'version' => $maxVersion + 1,
                'name' => $prompt->name,
                'system' => $prompt->system,
                'user_template' => $prompt->user_template,
                'json_schema' => $prompt->json_schema,
                'created_by' => auth()->id(),
                'change_note' => request('change_note'),
            ]);

            PromptResolver::forget($step);
        });

        activity()->causedBy(auth()->user())->performedOn($prompt)->log("Update wizard prompt L{$step}: {$data['name']}");

        return back()->with('success', "Prompt L{$step} disimpan (v".(WizardPromptVersion::where('wizard_prompt_id', $prompt->id)->max('version')).").");
    }

    public function test(Request $request, int $step)
    {
        $this->validateStep($step);

        $request->validate([
            'user_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $userMessage = $request->input('user_message');
        $dummy = PromptResolver::dummyVars($step, $userMessage);

        $prompt = PromptResolver::get($step);
        $rendered = PromptResolver::render($prompt['user_template'] ?? '', $dummy);

        $start = microtime(true);
        try {
            $ai = new AiService();
            // Use test with max_tokens small? AiService already controls max_tokens.
            // Pass dummy as input, with history empty
            $result = $ai->generate($step, $dummy);
            $latency = (int) ((microtime(true) - $start) * 1000);

            $parsed = null;
            $parseError = null;
            if (isset($result['content'])) {
                $content = $result['content'];
                // Try parse JSON if looks like JSON
                $trim = trim($content);
                // Remove markdown fence if any
                $trim = preg_replace('/^```(?:json)?\s*/i', '', $trim);
                $trim = preg_replace('/\s*```$/', '', $trim);
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $parsed = $decoded;
                } else {
                    // Try extract JSON substring
                    if (preg_match('/\{.*\}/s', $trim, $m)) {
                        $decoded2 = json_decode($m[0], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $parsed = $decoded2;
                        } else {
                            $parseError = json_last_error_msg();
                        }
                    } else {
                        $parseError = json_last_error_msg();
                    }
                }
            }

            return response()->json([
                'step' => $step,
                'prompt_source' => $prompt['source'] ?? 'unknown',
                'rendered_system' => $prompt['system'],
                'rendered_user' => $rendered,
                'dummy_vars' => $dummy,
                'ai_raw' => $result,
                'ai_parsed' => $parsed,
                'parse_error' => $parseError,
                'latency_ms' => $latency,
            ]);
        } catch (\Throwable $e) {
            $latency = (int) ((microtime(true) - $start) * 1000);
            return response()->json([
                'step' => $step,
                'prompt_source' => $prompt['source'] ?? 'unknown',
                'rendered_system' => $prompt['system'],
                'rendered_user' => $rendered,
                'dummy_vars' => $dummy,
                'ai_raw' => ['error' => $e->getMessage()],
                'ai_parsed' => null,
                'parse_error' => $e->getMessage(),
                'latency_ms' => $latency,
            ], 500);
        }
    }

    public function versions(int $step)
    {
        $this->validateStep($step);
        $prompt = WizardPrompt::where('step', $step)->first();
        if (! $prompt) {
            return response()->json(['versions' => []]);
        }
        $versions = WizardPromptVersion::where('wizard_prompt_id', $prompt->id)
            ->with('creator:id,name,email')
            ->orderByDesc('version')
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'version' => $v->version,
                'name' => $v->name,
                'system' => $v->system,
                'user_template' => $v->user_template,
                'json_schema' => $v->json_schema,
                'created_by' => $v->creator?->name ?? 'system',
                'change_note' => $v->change_note,
                'created_at' => $v->created_at?->toDateTimeString(),
            ]);

        return response()->json(['versions' => $versions, 'prompt' => $prompt]);
    }

    public function rollback(int $step, int $version)
    {
        $this->validateStep($step);
        $prompt = WizardPrompt::where('step', $step)->firstOrFail();
        $target = WizardPromptVersion::where('wizard_prompt_id', $prompt->id)->where('version', $version)->firstOrFail();

        DB::transaction(function () use ($prompt, $target, $step) {
            $prompt->update([
                'name' => $target->name,
                'system' => $target->system,
                'user_template' => $target->user_template,
                'json_schema' => $target->json_schema,
                'is_active' => true,
                'updated_by' => auth()->id(),
            ]);

            $max = WizardPromptVersion::where('wizard_prompt_id', $prompt->id)->max('version') ?? 0;
            WizardPromptVersion::create([
                'wizard_prompt_id' => $prompt->id,
                'version' => $max + 1,
                'name' => $target->name,
                'system' => $target->system,
                'user_template' => $target->user_template,
                'json_schema' => $target->json_schema,
                'created_by' => auth()->id(),
                'change_note' => "Rollback ke v{$target->version}",
            ]);

            PromptResolver::forget($step);
        });

        activity()->causedBy(auth()->user())->performedOn($prompt)->log("Rollback wizard prompt L{$step} ke v{$version}");

        return back()->with('success', "Rollback L{$step} ke v{$version} berhasil (versi baru dibuat).");
    }

    private function validateStep(int $step): void
    {
        if ($step < 1 || $step > 8) {
            abort(404, "Step harus 1..8");
        }
    }
}
