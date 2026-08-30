<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSection;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
        $this->ensureSections($project);

        $sections = $project->sections()->orderBy('step')->get()->keyBy('step');
        $currentSection = $sections[$step] ?? null;

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

        $l1final = $sections[1]?->content['final_output'] ?? null;
        $l2final = $sections[2]?->content['final_output'] ?? null;

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
                'has_content' => !empty($s->content['final_output'] ?? null),
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
        if (!in_array($step, [1,2,3])) abort(404, 'Step chat hanya 1-3 (L4-8 soon)');

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
            // cek apakah ada URL yang terdeteksi sebagai empty message tetap butuh handle? No.
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
        $userCount = collect($history)->where('role','user')->count();
        if ($userCount >= $maxChats) {
            return response()->json(['error' => "Maksimal $maxChats percakapan per langkah tercapai. Simpan sebagai Final dan lanjut ke langkah berikutnya.", 'code' => 'max_chats_reached'], 429);
        }

        // handle attachments — file extract + image base64
        $attachmentTexts = [];
        $imagesBase64 = [];
        $storedAttachments = [];
        if ($hasAttachment) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("wizard/{$project->id}", 'local');
                $storedAttachments[] = ['path' => $path, 'orig' => $file->getClientOriginalName(), 'mime' => $file->getMimeType()];
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['txt','md'])) {
                    try {
                        $txt = Storage::disk('local')->get($path);
                        $attachmentTexts[] = "[File {$file->getClientOriginalName()}]:\n" . mb_substr($txt, 0, 5000);
                    } catch (\Throwable $e) {
                        $attachmentTexts[] = "[File {$file->getClientOriginalName()} — gagal baca]";
                    }
                } elseif ($ext === 'pdf') {
                    try {
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf = $parser->parseFile(Storage::disk('local')->path($path));
                        $txt = trim($pdf->getText());
                        if ($txt !== '') {
                            $attachmentTexts[] = "[PDF {$file->getClientOriginalName()}]:\n" . mb_substr($txt, 0, 5000);
                        } else {
                            $attachmentTexts[] = "[PDF {$file->getClientOriginalName()} — tanpa teks (hasil scan?). Jika ini screenshot/mockup, upload sebagai gambar agar AI bisa lihat langsung.]";
                        }
                    } catch (\Throwable $e) {
                        $attachmentTexts[] = "[PDF {$file->getClientOriginalName()} — gagal ekstrak teks: {$e->getMessage()}]";
                    }
                } elseif (in_array($ext, ['jpg','jpeg','png','webp'])) {
                    $attachmentTexts[] = "[Gambar {$file->getClientOriginalName()} — akan dikirim sebagai vision ke AI jika model support]";
                    try { $imagesBase64[] = base64_encode(Storage::disk('local')->get($path)); } catch (\Throwable $e) {}
                } else {
                    $attachmentTexts[] = "[File {$file->getClientOriginalName()} — lampiran]";
                }
            }
        }

        // CLONE URL HANDLING — WIZARD-CHAT-SPEC §3.1
        // Backend deteksi https:// di user_message → Http::timeout(8)->get(url) → strip_tags → plain text 5k char → inject ke {{attachment_text}}
        $cloneFetched = [];
        if ($message !== '' && preg_match_all('/https?:\/\/[^\s"\'<>\)\]]+/i', $message, $matches)) {
            $urls = array_unique(array_slice($matches[0], 0, 2)); // max 2 URL per message to avoid abuse
            foreach ($urls as $url) {
                // Clean trailing punctuation
                $url = rtrim($url, '.,;!?)');
                try {
                    $res = Http::timeout(8)->withHeaders(['User-Agent' => 'BuatPRD-Bot/1.0'])->get($url);
                    if ($res->successful()) {
                        $body = $res->body();
                        // SPA (Nuxt/Vue) sering simpan konten di <script>/<style> — strip_tags() TIDAK
                        // menghapus isinya, hasilnya noise JS/CSS dan fitur inti ilang (kasus meterpams.com:
                        // 2369 char noise vs 5579 char bersih). Buang dulu sebelum strip_tags.
                        $body = preg_replace('/<(script|style|noscript|template)\b[^>]*>.*?<\/\1>/is', ' ', $body);
                        $body = preg_replace('/<!--.*?-->/s', ' ', $body);
                        $text = strip_tags($body);
                        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $text = preg_replace('/\s+/', ' ', $text);
                        $text = trim($text);
                        $text = mb_substr($text, 0, 5000);
                        if ($text !== '') {
                            $cloneFetched[] = $text;
                            $attachmentTexts[] = "[Ekstraksi URL {$url} — {$this->truncateUrlTextLabel($text)}]:\n" . $text;
                        } else {
                            $attachmentTexts[] = "[URL {$url} — fetch OK tapi konten kosong]";
                        }
                    } else {
                        $attachmentTexts[] = "[URL {$url} — gagal fetch (HTTP {$res->status()})]";
                    }
                } catch (\Throwable $e) {
                    $attachmentTexts[] = "[URL {$url} — gagal fetch: {$e->getMessage()}]";
                }
            }
        }

        $attachmentText = !empty($attachmentTexts) ? implode("\n\n", $attachmentTexts) : '-';

        // build context per spec §1: inject final_output langkah sebelumnya + history 3 turn + attachment_text
        $project->load('sections');
        $sectionsByStep = $project->sections->keyBy('step');
        $step1Content = $sectionsByStep[1]?->content['final_output'] ?? null;
        $step2Content = $sectionsByStep[2]?->content['final_output'] ?? null;
        $step3Content = $sectionsByStep[3]?->content['final_output'] ?? null;

        $enriched = [
            'title' => $project->title,
            'description' => $project->description ?? '-',
            'user_message' => $message ?: '(hanya lampiran)',
            'attachment_text' => $attachmentText,
            'history' => array_slice($history, -3),
            'step1_content' => $step1Content,
            'step1_final' => $step1Content,
            'step2_final' => $step2Content,
            'step3_final' => $step3Content,
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

        // deduct quota 1 kredit
        $this->deductQuota($membership);

        // parse structured
        $structured = null;
        $rawContent = $result['content'] ?? '';
        $isMock = $result['mock'] ?? false;
        if ($isMock) {
            // Mock provider (sk-test) — jangan parse raw "[MOCK] input: {...}" yang berisi input JSON, langsung pakai placeholder deterministik
            if ($step === 1) {
                $structured = [
                    'problem_statement' => 'Mock problem untuk: '.mb_substr($message,0,120),
                    'target_personas' => [['nama'=>'Persona Mock','deskripsi'=>'User umum','pain'=>'Butuh solusi cepat','goal'=>'Selesaikan masalah']],
                    'value_proposition' => 'Mock UVP — solusi cepat untuk '.$project->title,
                    'pain_points' => ['Mock pain 1','Manual rekap'],
                    'asumsi' => ['[Asumsi] mock — diferensiasi belum dijawab'],
                    'pertanyaan_klarifikasi' => ['Target pasar spesifik?','Pricing berbeda?'],
                ];
            } elseif ($step === 2) {
                $structured = [
                    'industri_terdeteksi' => $request->input('industry','SaaS'),
                    'kpis' => [['nama'=>'Mock KPI','definisi'=>'Mock definition','target'=>'60%','tipe'=>'Leading','cara_ukur'=>'event tracking','prioritas'=>'Must']],
                    'north_star' => ['metric'=>'Mock North Star','alasan'=>'Aktivasi inti'],
                    'rekomendasi_instrumentasi' => ['Mixpanel','GA4'],
                    'asumsi_baseline' => ['[Asumsi] baseline 0'],
                ];
            } elseif ($step === 3) {
                $structured = [
                    'architecture' => [
                        'recommended'=>'modular_monolith',
                        'stack'=>['Laravel','Vue','MySQL'],
                        'alasan'=>'Mock — tim kecil, modular monolith paling pragmatis',
                        'opsi'=>[
                            ['id'=>'modular_monolith','label'=>'Modular Monolith','pro'=>['simpel','deployment 1','transaksi konsisten'],'kontra'=>['skala vertikal terbatas'],'cocok'=>true],
                            ['id'=>'monolith','label'=>'Monolith','pro'=>['paling simpel'],'kontra'=>['campur domain'],'cocok'=>false],
                            ['id'=>'microservice','label'=>'Microservice','pro'=>['scale per service'],'kontra'=>['overkill','ops berat'],'cocok'=>false],
                        ]
                    ],
                    'modules'=>[
                        ['id'=>'auth','nama'=>'Auth & Role','deskripsi'=>'Login, role member/admin','prioritas'=>'Must','estimasi'=>'2 hari','checked'=>true],
                        ['id'=>'project_crud','nama'=>'Project CRUD','deskripsi'=>'Buat & kelola PRD workspace','prioritas'=>'Must','estimasi'=>'3 hari','checked'=>true],
                        ['id'=>'wizard','nama'=>'Wizard L1-L3 Chat','deskripsi'=>'Chat iterative per step','prioritas'=>'Must','checked'=>true],
                    ],
                    'folder_structure'=>['root'=>'app','tree'=>[['name'=>'app','type'=>'folder','children'=>[['name'=>'Http','type'=>'folder','children'=>[['name'=>'Controllers','type'=>'folder']]],['name'=>'Models','type'=>'folder']]]]]
                ];
            }
        } else {
            $structured = $this->tryParseJson($rawContent);
            if (!$structured) {
                // Jika parse gagal untuk provider real, fallback mock agar UI tetap render (tapi tandai di raw)
                // Biarkan null, tapi coba fallback step-specific biar tidak blank
                if ($step === 1) {
                    $structured = [
                        'problem_statement' => 'Parse gagal — raw: '.mb_substr($rawContent,0,120),
                        'target_personas' => [['nama'=>'Fallback','deskripsi'=>'-','pain'=>'-','goal'=>'-']],
                        'value_proposition' => 'Fallback UVP',
                        'pain_points' => [],
                        'asumsi' => ['[Asumsi] parse gagal, cek prompt L1'],
                        'pertanyaan_klarifikasi' => [],
                    ];
                } elseif ($step === 2) {
                    $structured = [
                        'industri_terdeteksi' => $request->input('industry','SaaS'),
                        'kpis' => [['nama'=>'Fallback KPI','definisi'=>'parse gagal','target'=>'-','tipe'=>'Leading','cara_ukur'=>'-','prioritas'=>'Must']],
                        'north_star' => ['metric'=>'Fallback','alasan'=>'-'],
                        'rekomendasi_instrumentasi' => [],
                        'asumsi_baseline' => [],
                    ];
                } elseif ($step === 3) {
                    $structured = [
                        'architecture' => [
                            'recommended'=>'modular_monolith',
                            'stack'=>['Laravel','Vue'],
                            'alasan'=>'Fallback parse gagal',
                            'opsi'=>[['id'=>'modular_monolith','label'=>'Modular Monolith','pro'=>['fallback'],'kontra'=>[],'cocok'=>true]]
                        ],
                        'modules'=>[['id'=>'fallback','nama'=>'Fallback Module','deskripsi'=>'parse gagal','prioritas'=>'Must','checked'=>true]],
                        'folder_structure'=>['root'=>'app','tree'=>[['name'=>'app','type'=>'folder']]]
                    ];
                }
            }
        }

        // update history
        $newUserEntry = [
            'role' => 'user',
            'text' => $message ?: '(lampiran)',
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

        $this->updateProgress($project);

        try { activity()->causedBy($user)->performedOn($project)->log("wizard-chat step $step"); } catch (\Throwable $e) {}

        return response()->json([
            'history' => $history,
            'final_output' => $structured,
            'raw' => $rawContent,
            'mock' => $isMock,
            'model' => $result['model'] ?? null,
            'provider' => $result['provider'] ?? null,
            'quota' => [
                'remaining' => $membership->remainingQuota(),
                'credit' => $membership->credit_balance,
            ],
            'attachment_text_preview' => mb_substr($attachmentText, 0, 300),
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
        $raw = $section3?->content;
        $arr = is_array($raw) ? $raw : (is_string($raw) ? (json_decode($raw,true)?:[]) : []);
        $tree = $arr['final_output']['folder_structure']['tree'] ?? $arr['final_output']['folder_structure'] ?? null;
        if (!$tree) return response()->json(['error'=>'Folder structure belum ada. Generate di Langkah 3 dulu.'], 404);
        $root = $arr['final_output']['folder_structure']['root'] ?? $project->slug ?? 'project';
        if (isset($tree['tree'])) $tree = $tree['tree'];

        $tmp = tempnam(sys_get_temp_dir(), 'wizard_zip_');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::CREATE)!==true) abort(500,'Gagal buat zip');
        $this->addTreeToZip($zip, is_array($tree) ? $tree : [], '');
        $zip->addFromString('README.md', "# {$project->title}\nGenerated by BuatPRD Wizard step 3\nRoot: {$root}\n");
        $zip->close();
        return response()->download($tmp, $root.'.zip')->deleteFileAfterSend(true);
    }

    private function tryParseJson(string $raw): ?array
    {
        $trim = trim($raw);
        // Remove think tags from OpenCode if any
        $trim = preg_replace('/<think>.*?<\/think>/s', '', $trim);
        $trim = trim($trim);
        $decoded = json_decode($trim, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
        if (preg_match('/```json\s*(.*?)\s*```/s', $trim, $m)) {
            $d2 = json_decode(trim($m[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) return $d2;
        }
        if (preg_match('/```\s*(.*?)\s*```/s', $trim, $m)) {
            $d2 = json_decode(trim($m[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) return $d2;
        }
        // Try extract outermost {}
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $trim, $m)) {
            $d2 = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) return $d2;
        }
        return null;
    }

    private function truncateUrlTextLabel(string $txt): string
    {
        $len = mb_strlen($txt);
        return $len > 80 ? mb_substr($txt, 0, 80).'…'." ({$len} char)" : "({$len} char)";
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
        $membership->refresh();
    }

    private function updateProgress(Project $project): void
    {
        $filled = $project->sections()->get()->filter(fn($s) => !empty((is_array($s->content) ? $s->content : (json_decode($s->content ?? '', true)?:[]))['final_output'] ?? null))->count();
        $progress = (int) round($filled/8*100);
        $project->update(['progress'=>$progress]);
        if ($filled >= $project->current_step) {
            $project->update(['current_step' => min(8, $filled+1)]);
        }
    }
}
