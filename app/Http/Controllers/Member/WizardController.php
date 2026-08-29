<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSection;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WizardController extends Controller
{
    public function show(Request $request, Project $project, int $step, AiService $ai): Response
    {
        if ($project->user_id !== $request->user()->id) abort(403);
        if ($step < 1 || $step > 8) abort(404);
        $project->load('sections');
        // ensure 8 sections exist
        $this->ensureSections($project);

        $sections = $project->sections()->orderBy('step')->get()->keyBy('step');
        $currentSection = $sections[$step] ?? null;

        // normalize content to array
        $history = [];
        $finalOutput = null;
        if ($currentSection && is_array($currentSection->content)) {
            $history = $currentSection->content['history'] ?? [];
            $finalOutput = $currentSection->content['final_output'] ?? null;
        } elseif ($currentSection && is_string($currentSection->content)) {
            $decoded = json_decode($currentSection->content, true);
            if (is_array($decoded)) {
                $history = $decoded['history'] ?? [];
                $finalOutput = $decoded['final_output'] ?? null;
            }
        }

        // for L2 need L1 final, for L3 need L1+L2
        $l1final = $sections[1]?->content['final_output'] ?? $sections[1]?->content ?? null;
        $l2final = $sections[2]?->content['final_output'] ?? $sections[2]?->content ?? null;

        return Inertia::render('Member/Wizard/Show', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'slug' => $project->slug,
                'description' => $project->description,
                'current_step' => $project->current_step,
                'progress' => $project->progress,
            ],
            'step' => $step,
            'stepTitle' => ProjectSection::STEP_TITLES[$step] ?? "Langkah $step",
            'section' => $currentSection ? [
                'id' => $currentSection->id,
                'step' => $currentSection->step,
                'title' => $currentSection->title,
                'history' => $history,
                'final_output' => $finalOutput,
                'ai_generated' => $currentSection->ai_generated,
            ] : null,
            'allSections' => $sections->map(fn($s) => [
                'step' => $s->step,
                'title' => $s->title,
                'has_content' => !empty($s->content['final_output'] ?? $s->content),
                'ai_generated' => $s->ai_generated,
            ])->values(),
            'l1final' => $l1final,
            'l2final' => $l2final,
            'availableModels' => $ai->availableModels(),
        ]);
    }

    public function chat(Request $request, Project $project, int $step, AiService $ai)
    {
        if ($project->user_id !== $request->user()->id) abort(403);
        if (!in_array($step, [1,2,3])) abort(404, 'Step chat hanya 1-3');

        $request->validate([
            'message' => ['nullable','string','max:5000'],
            'model' => ['nullable','string','max:100'],
            'attachments' => ['nullable','array','max:3'],
            'attachments.*' => ['file','mimes:pdf,txt,md,jpg,jpeg,png,webp','max:5120'],
            'stack' => ['nullable','string','max:2000'],
            'industry' => ['nullable','string','max:100'],
        ]);

        $message = trim($request->input('message',''));
        $hasAttachment = $request->hasFile('attachments');
        if ($message === '' && !$hasAttachment) {
            return response()->json(['error' => 'Pesan atau lampiran wajib diisi'], 422);
        }

        // quota check
        $user = $request->user();
        $membership = $user->activeMembership()->with('tier')->first() ?? $user->membership()->latest()->first();
        if (!$membership || (!$user->canUseAi())) {
            return response()->json(['error' => 'Kuota AI habis. Upgrade paket atau top up kredit.', 'code' => 'quota_exceeded'], 403);
        }

        // max chats per step
        $section = $this->getOrCreateSection($project, $step);
        $contentArr = is_array($section->content) ? $section->content : (json_decode($section->content ?? '', true) ?: []);
        $history = $contentArr['history'] ?? [];
        $maxChats = config('ai.wizard.max_chats_per_step', 10);
        // count user messages in history
        $userCount = collect($history)->where('role','user')->count();
        if ($userCount >= $maxChats) {
            return response()->json(['error' => "Maksimal $maxChats percakapan per langkah tercapai. Simpan sebagai Final dan lanjut ke langkah berikutnya.", 'code' => 'max_chats_reached'], 429);
        }

        // handle attachments
        $attachmentTexts = [];
        $imagesBase64 = [];
        $storedAttachments = [];
        if ($hasAttachment) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("wizard/{$project->id}", 'local');
                $storedAttachments[] = ['path' => $path, 'orig' => $file->getClientOriginalName(), 'mime' => $file->getMimeType()];
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['txt','md'])) {
                    $attachmentTexts[] = "[File {$file->getClientOriginalName()}]:\n" . Storage::disk('local')->get($path);
                } elseif ($ext === 'pdf') {
                    $attachmentTexts[] = "[PDF {$file->getClientOriginalName()} — teks terekstrak tidak tersedia, AI akan anggap sebagai brief]";
                } elseif (in_array($ext, ['jpg','jpeg','png','webp'])) {
                    $attachmentTexts[] = "[Gambar {$file->getClientOriginalName()} — akan dikirim sebagai vision jika model support]";
                    $imagesBase64[] = base64_encode(Storage::disk('local')->get($path));
                }
            }
        }
        $attachmentText = !empty($attachmentTexts) ? implode("\n\n", $attachmentTexts) : '-';

        // build context
        $project->load('sections');
        $sectionsByStep = $project->sections->keyBy('step');
        $step1Content = $sectionsByStep[1]?->content['final_output'] ?? $sectionsByStep[1]?->content ?? null;
        $step2Content = $sectionsByStep[2]?->content['final_output'] ?? $sectionsByStep[2]?->content ?? null;

        $enriched = [
            'title' => $project->title,
            'description' => $project->description,
            'user_message' => $message ?: '(hanya lampiran)',
            'attachment_text' => $attachmentText,
            'history' => array_slice($history, -3),
            'step1_content' => $step1Content,
            'step1_final' => $step1Content,
            'step2_final' => $step2Content,
            'step2_content' => $step2Content,
            'stack' => $request->input('stack', '-'),
            'industry' => $request->input('industry', 'auto-deteksi'),
            'industry_or_auto' => $request->input('industry', 'auto-deteksi'),
            'images' => $imagesBase64,
        ];

        $modelValue = $request->input('model');

        $result = $ai->generate($step, $enriched, $modelValue);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 502);
        }

        // deduct quota
        $this->deductQuota($membership);

        // parse structured
        $structured = null;
        $rawContent = $result['content'] ?? '';
        $isMock = $result['mock'] ?? false;
        if (!$isMock) {
            $parsed = json_decode($rawContent, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                $structured = $parsed;
            } else {
                // try extract json from markdown fence
                if (preg_match('/```json\s*(.*?)\s*```/s', $rawContent, $m)) {
                    $parsed2 = json_decode($m[1], true);
                    if (json_last_error() === JSON_ERROR_NONE) $structured = $parsed2;
                }
            }
        } else {
            // mock: generate placeholder structured so UI can render
            if ($step === 1) {
                $structured = [
                    'problem_statement' => 'Mock problem untuk: '.$message,
                    'target_personas' => [['nama'=>'Persona Mock','deskripsi'=>'User umum','pain'=>'Butuh solusi cepat','goal'=>'Selesaikan masalah']],
                    'value_proposition' => 'Mock UVP',
                    'pain_points' => ['Mock pain'],
                    'asumsi' => ['[Asumsi] mock'],
                    'pertanyaan_klarifikasi' => [],
                ];
            } elseif ($step === 2) {
                $structured = [
                    'industri_terdeteksi' => $request->input('industry','SaaS'),
                    'kpis' => [['nama'=>'Mock KPI','definisi'=>'Mock','target'=>'60%','tipe'=>'Leading','cara_ukur'=>'event','prioritas'=>'Must']],
                    'north_star' => ['metric'=>'Mock NS','alasan'=>'-'],
                    'rekomendasi_instrumentasi' => [],
                    'asumsi_baseline' => [],
                ];
            } elseif ($step === 3) {
                $structured = [
                    'architecture' => [
                        'recommended'=>'modular_monolith',
                        'stack'=>['Laravel','Vue'],
                        'alasan'=>'Mock',
                        'opsi'=>[
                            ['id'=>'modular_monolith','label'=>'Modular Monolith','pro'=>['simpel'],'kontra'=>[],'cocok'=>true],
                            ['id'=>'monolith','label'=>'Monolit','pro'=>[],'kontra'=>[],'cocok'=>false],
                        ]
                    ],
                    'modules'=>[
                        ['id'=>'auth','nama'=>'Auth & Role','deskripsi'=>'Login','prioritas'=>'Must','estimasi'=>'3 hari','checked'=>true],
                        ['id'=>'pos','nama'=>'POS','deskripsi'=>'Kasir','prioritas'=>'Must','checked'=>true],
                    ],
                    'folder_structure'=>['root'=>'app','tree'=>[['name'=>'app','type'=>'folder','children'=>[['name'=>'Modules','type'=>'folder']]]]]
                ];
            }
        }

        // update history
        $newUserEntry = [
            'role' => 'user',
            'text' => $message,
            'attachments' => $storedAttachments,
            'model' => $result['model'] ?? $modelValue ?? '-',
            'created_at' => now()->toISOString(),
        ];
        $newAssistantEntry = [
            'role' => 'assistant',
            'text' => $rawContent,
            'structured' => $structured,
            'model' => $result['model'] ?? '-',
            'provider' => $result['provider'] ?? '-',
            'mock' => $isMock,
            'created_at' => now()->toISOString(),
        ];
        $history[] = $newUserEntry;
        $history[] = $newAssistantEntry;

        $contentArr['history'] = $history;
        if ($structured) $contentArr['final_output'] = $structured;
        $contentArr['updated_at'] = now()->toISOString();

        $section->update([
            'content' => $contentArr,
            'ai_generated' => true,
            'ai_prompt' => $message,
        ]);

        // update project progress
        $this->updateProgress($project);

        // activity log
        try { activity()->causedBy($user)->performedOn($project)->log("wizard-chat step $step"); } catch (\Throwable $e) {}

        return response()->json([
            'history' => $history,
            'final_output' => $structured,
            'mock' => $isMock,
            'model' => $result['model'] ?? null,
            'provider' => $result['provider'] ?? null,
            'quota' => [
                'remaining' => $membership->remainingQuota(),
                'credit' => $membership->credit_balance,
            ]
        ]);
    }

    public function saveFinal(Request $request, Project $project, int $step)
    {
        if ($project->user_id !== $request->user()->id) abort(403);
        if (!in_array($step, [1,2,3])) abort(404);
        $request->validate([
            'final_output' => ['required','array'],
        ]);
        $section = $this->getOrCreateSection($project, $step);
        $contentArr = is_array($section->content) ? $section->content : (json_decode($section->content ?? '', true) ?: []);
        $contentArr['final_output'] = $request->input('final_output');
        $contentArr['manual_save_at'] = now()->toISOString();
        $section->update(['content' => $contentArr, 'ai_generated' => true]);
        $this->updateProgress($project);
        return response()->json(['ok'=>true, 'final_output'=>$contentArr['final_output']]);
    }

    public function downloadZip(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) abort(403);
        $section3 = $project->sections()->where('step',3)->first();
        $tree = $section3?->content['final_output']['folder_structure']['tree'] ?? $section3?->content['final_output']['folder_structure'] ?? null;
        if (!$tree) return response()->json(['error'=>'Folder structure belum ada. Generate di Langkah 3 dulu.'], 404);
        // normalize
        $root = $section3->content['final_output']['folder_structure']['root'] ?? $project->slug ?? 'project';
        // if tree is not array wrap
        if (isset($tree['tree'])) $tree = $tree['tree'];

        $tmp = tempnam(sys_get_temp_dir(), 'wizard_zip_');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::CREATE)!==true) abort(500,'Gagal buat zip');
        $this->addTreeToZip($zip, is_array($tree) ? $tree : [], '');
        // add README
        $zip->addFromString('README.md', "# {$project->title}\nGenerated by BuatPRD Wizard step 3\n");
        $zip->close();
        return response()->download($tmp, $root.'.zip')->deleteFileAfterSend(true);
    }

    private function addTreeToZip(\ZipArchive $zip, array $nodes, string $prefix): void
    {
        foreach ($nodes as $node) {
            $name = $node['name'] ?? 'untitled';
            $type = $node['type'] ?? 'folder';
            $path = $prefix ? $prefix.'/'.$name : $name;
            if ($type === 'folder') {
                $zip->addEmptyDir($path);
                if (!empty($node['children'])) $this->addTreeToZip($zip, $node['children'], $path);
            } else {
                $zip->addFromString($path, "// {$name}\n");
            }
        }
    }

    private function ensureSections(Project $project): void
    {
        $existing = $project->sections()->pluck('step')->toArray();
        foreach (range(1,8) as $s) {
            if (!in_array($s, $existing)) {
                ProjectSection::create([
                    'project_id' => $project->id,
                    'step' => $s,
                    'title' => ProjectSection::STEP_TITLES[$s],
                    'content' => ['history'=>[],'final_output'=>null],
                ]);
            }
        }
    }

    private function getOrCreateSection(Project $project, int $step): ProjectSection
    {
        $this->ensureSections($project);
        return $project->sections()->where('step',$step)->firstOrFail();
    }

    private function deductQuota($membership): void
    {
        if ($membership->credit_balance > 0 && $membership->ai_quota_used >= $membership->ai_quota_total) {
            $membership->decrement('credit_balance', 1);
        } else {
            $membership->increment('ai_quota_used', 1);
        }
    }

    private function updateProgress(Project $project): void
    {
        $filled = $project->sections()->whereNotNull('content')->get()->filter(fn($s) => !empty($s->content['final_output'] ?? null))->count();
        $progress = (int) round($filled/8*100);
        $project->update(['progress'=>$progress, 'current_step'=>max($project->current_step, $filled+1)]);
    }
}
