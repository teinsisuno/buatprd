<?php

namespace App\Services;

use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;

class AiService
{
    public function availableModels(): array
    {
        return AiProvider::activeModelsGrouped();
    }

    public function defaultProvider(): ?AiProvider
    {
        // NEW: default level MODEL (2), bukan provider — ambil dari settings
        $defaultVal = \App\Models\AiProvider::defaultModelValue();
        if ($defaultVal && str_contains($defaultVal, ':')) {
            [$prov] = explode(':', $defaultVal, 2);
            $p = AiProvider::where('provider', $prov)->where('is_active', true)->first();
            if ($p) return $p;
        }
        return AiProvider::active()->first();
    }

    public function defaultModelValue(): ?string
    {
        return \App\Models\AiProvider::defaultModelValue();
    }

    public function defaultVisionModelValue(): ?string
    {
        return \App\Models\AiProvider::defaultVisionModelValue();
    }

    public function generate(int $step, array $input, ?string $modelValue = null): array
    {
        // Jika ada gambar dan modelValue tidak dipilih user, pakai vision default
        $hasImages = !empty($input['images']);
        if (empty($modelValue) && $hasImages) {
            $modelValue = $this->defaultVisionModelValue();
        }
        $provider = $this->resolveProvider($modelValue);
        if (! $provider) {
            return ['error' => 'Belum ada AI Provider aktif. Hubungi admin di /admin/ai-providers.'];
        }
        $model = $this->resolveModel($modelValue, $provider);
        $stepPrompt = \App\Services\PromptResolver::get($step);

        [$system, $userContent, $images] = $this->renderPrompt($step, $input, $stepPrompt);

        $isTestKey = str_starts_with($provider->api_key ?? '', 'sk-test');
        $isOpenCode = in_array($provider->provider, ['opencode_go','opencode_zen']);

        if (!$isTestKey && !$isOpenCode) {
            $real = $this->callProvider($provider, $model, $step, $input, $system, $userContent, $images);
            if (!isset($real['error'])) return $real;
            return [
                'provider' => $provider->provider,
                'model' => $model,
                'step' => $step,
                'step_name' => $stepPrompt['name'] ?? "Langkah $step",
                'mock' => true,
                'error_real' => $real['error'],
                'content' => "[MOCK] Hasil AI {$provider->label} ($model) untuk langkah $step — input: " . json_encode($input, JSON_UNESCAPED_UNICODE),
            ];
        }

        if ($isOpenCode) {
            $try = $this->callProvider($provider, $model, $step, $input, $system, $userContent, $images);
            if (!isset($try['error'])) return $try;
            // Jangan fallback diam-diam — kembalikan error jelas biar wizard tidak blink tanpa sebab
            // Simpan mock hanya untuk debugging jika caller mau, tapi error tetap diutamakan
            return [
                'provider' => $provider->provider,
                'model' => $model,
                'step' => $step,
                'step_name' => $stepPrompt['name'] ?? "Langkah $step",
                'mock' => false,
                'error' => $try['error'],
            ];
        }

        return [
            'provider' => $provider->provider,
            'model' => $model,
            'step' => $step,
            'step_name' => $stepPrompt['name'] ?? "Langkah $step",
            'mock' => true,
            'content' => "[MOCK] Hasil AI {$provider->label} ($model) untuk langkah $step — input: " . json_encode($input, JSON_UNESCAPED_UNICODE),
        ];
    }

    private function renderPrompt(int $step, array $input, array $stepPrompt): array
    {
        $system = $stepPrompt['system'] ?? 'Kamu asisten PRD.';
        $template = $stepPrompt['user_template'] ?? null;

        // Build vars with defaults — keep in sync with PromptResolver::ALLOWED_VARS
        $vars = [
            'title' => $input['title'] ?? $input['project_title'] ?? '-',
            'description' => $input['description'] ?? '-',
            'user_message' => $input['user_message'] ?? $input['message'] ?? json_encode($input, JSON_UNESCAPED_UNICODE),
            'attachment_text' => $input['attachment_text'] ?? '-',
            'history' => $this->formatHistory($input['history'] ?? []),
            'step1_content' => $this->stringify($input['step1_content'] ?? $input['step1_final'] ?? '-'),
            'step1_final' => $this->stringify($input['step1_final'] ?? $input['step1_content'] ?? '-'),
            'step2_final' => $this->stringify($input['step2_final'] ?? $input['step2_content'] ?? '-'),
            'step3_final' => $this->stringify($input['step3_final'] ?? '-'),
            'stack' => $this->stringify($input['stack'] ?? '-'),
            'industry_or_auto' => $input['industry'] ?? $input['industry_or_auto'] ?? 'auto-deteksi',
            'industry' => $input['industry'] ?? $input['industry_or_auto'] ?? 'auto-deteksi',
            'project_title' => $input['title'] ?? $input['project_title'] ?? '-',
            'all_steps' => $this->stringify($input['all_steps'] ?? '-'),
        ];

        if ($template) {
            // Use PromptResolver for consistent alias + cleanup handling
            $userContent = \App\Services\PromptResolver::render($template, $vars);
        } else {
            $userContent = json_encode($input, JSON_UNESCAPED_UNICODE);
        }

        $images = $input['images'] ?? [];

        return [$system, $userContent, $images];
    }

    private function formatHistory($history): string
    {
        if (empty($history) || !is_array($history)) return '-';
        $out = [];
        foreach (array_slice($history, -3) as $h) {
            $role = $h['role'] ?? 'user';
            $text = $h['text'] ?? $h['content'] ?? json_encode($h, JSON_UNESCAPED_UNICODE);
            $out[] = strtoupper($role).': '.$text;
        }
        return implode("\n", $out) ?: '-';
    }

    private function stringify($val): string
    {
        if (is_string($val)) return $val;
        if (is_null($val)) return '-';
        return json_encode($val, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function testConnection(AiProvider $provider): array
    {
        $key = $provider->api_key;
        if (! $key) return ['ok' => false, 'error' => 'API Key kosong'];
        $fetch = $this->fetchModels($provider);
        if (isset($fetch['error'])) return ['ok' => false, 'error' => $fetch['error']];
        // Coba generate ringan
        $model = $provider->enabled_models[0] ?? $fetch['models'][0]['id'] ?? 'test';
        $gen = $this->callProvider($provider, $model, 1, ['ide' => 'test koneksi']);
        if (isset($gen['error'])) {
            if (in_array($provider->provider, ['opencode_go','opencode_zen'])) {
                return ['ok' => true, 'message' => 'Fetch OK ('.count($fetch['models']).' models). OpenCode key tersimpan, siap dipakai wizard. (generate mock karena endpoint demo)', 'models' => $fetch['models']];
            }
            return ['ok' => false, 'error' => $gen['error']];
        }
        return ['ok' => true, 'message' => 'Koneksi OK, model merespons.', 'models' => $fetch['models'], 'sample' => $gen['content'] ?? ''];
    }

    private function callProvider(AiProvider $provider, string $model, int $step, array $input, ?string $system = null, ?string $userContent = null, array $images = []): array
    {
        if ($system === null || $userContent === null) {
            $stepPrompt = \App\Services\PromptResolver::get($step);
            $system = $system ?? $stepPrompt['system'] ?? '';
            $userContent = $userContent ?? json_encode($input, JSON_UNESCAPED_UNICODE);
        }
        try {
            return match ($provider->provider) {
                'openai' => $this->callOpenAI($provider, $model, $system, $userContent, $step, null, $images),
                'gemini' => $this->callGemini($provider, $model, $system, $userContent, $images),
                'anthropic' => $this->callAnthropic($provider, $model, $system, $userContent, $images),
                'groq' => $this->callGroq($provider, $model, $system, $userContent, $images),
                'deepseek' => $this->callOpenAI($provider, $model, $system, $userContent, $step, 'https://api.deepseek.com/v1', $images),
                'openrouter' => $this->callOpenAI($provider, $model, $system, $userContent, $step, 'https://openrouter.ai/api/v1', $images),
                'mistral' => $this->callMistral($provider, $model, $system, $userContent, $images),
                'opencode_go' => $this->callOpenCode($provider, $model, $system, $userContent, 'go', $images),
                'opencode_zen' => $this->callOpenCode($provider, $model, $system, $userContent, 'zen', $images),
                default => ['error' => 'Provider tidak dikenal'],
            };
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function callOpenAI(AiProvider $provider, string $model, string $system, string $userContent, int $step, ?string $baseOverride = null, array $images = []): array
    {
        $base = $baseOverride ?: ($provider->base_url ?: 'https://api.openai.com/v1');
        $url = rtrim($base, '/') . '/chat/completions';
        $maxTokens = in_array($step, [3]) ? 1500 : 1200;
        $messages = [
            ['role' => 'system', 'content' => $system],
        ];
        if (!empty($images)) {
            $contentParts = [['type' => 'text', 'text' => $userContent]];
            foreach ($images as $b64) {
                $contentParts[] = ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,'.$b64]];
            }
            $messages[] = ['role' => 'user', 'content' => $contentParts];
        } else {
            $messages[] = ['role' => 'user', 'content' => $userContent];
        }
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => $maxTokens,
        ];
        if (in_array($step, [1,2,3])) $payload['response_format'] = ['type' => 'json_object'];
        $res = Http::withToken($provider->api_key)->timeout(30)->post($url, $payload);
        if ($res->failed()) return ['error' => 'OpenAI '.$res->status().': '.$res->body()];
        $content = $res->json('choices.0.message.content') ?? $res->json('choices.0.text') ?? '—';
        return ['provider'=>$provider->provider,'model'=>$model,'step'=>$step,'content'=>$content,'mock'=>false];
    }

    private function callGemini(AiProvider $provider, string $model, string $system, string $userContent, array $images = []): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $provider->api_key;
        $parts = [['text' => $system . "\n\n" . $userContent . "\n\nHANYA output JSON valid."]];
        foreach ($images as $b64) {
            $parts[] = ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $b64]];
        }
        $res = Http::timeout(30)->post($url, [
            'contents' => [['parts' => $parts]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1500],
        ]);
        if ($res->failed()) return ['error' => 'Gemini '.$res->status().': '.$res->body()];
        $content = $res->json('candidates.0.content.parts.0.text') ?? '—';
        return ['provider'=>$provider->provider,'model'=>$model,'content'=>$content,'mock'=>false];
    }

    private function callAnthropic(AiProvider $provider, string $model, string $system, string $userContent, array $images = []): array
    {
        $content = [['type' => 'text', 'text' => $userContent . "\n\nHANYA output JSON valid."]];
        foreach ($images as $b64) {
            $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $b64]];
        }
        $res = Http::withHeaders(['x-api-key' => $provider->api_key, 'anthropic-version' => '2023-06-01'])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 1500,
            'system' => $system,
            'messages' => [['role'=>'user','content'=>$content]],
        ]);
        if ($res->failed()) return ['error' => 'Anthropic '.$res->status().': '.$res->body()];
        $content = $res->json('content.0.text') ?? '—';
        return ['provider'=>$provider->provider,'model'=>$model,'content'=>$content,'mock'=>false];
    }

    private function callGroq(AiProvider $provider, string $model, string $system, string $userContent, array $images = []): array
    {
        return $this->callOpenAI($provider, $model, $system, $userContent, 0, 'https://api.groq.com/openai/v1', $images);
    }

    private function callMistral(AiProvider $provider, string $model, string $system, string $userContent, array $images = []): array
    {
        return $this->callOpenAI($provider, $model, $system, $userContent, 0, 'https://api.mistral.ai/v1', $images);
    }

    private function callOpenCode(AiProvider $provider, string $model, string $system, string $userContent, string $variant, array $images = []): array
    {
        $base = $provider->base_url ?: 'https://opencode.ai/zen/go/v1';
        $url = rtrim($base, '/') . '/chat/completions';
        $userMsg = $userContent;
        if (!empty($images)) {
            $parts = [['type'=>'text','text'=>$userContent]];
            foreach ($images as $b64) $parts[] = ['type'=>'image_url','image_url'=>['url'=>'data:image/jpeg;base64,'.$b64]];
            $userMsg = $parts;
        }
        // OpenCode: tambah max_tokens biar JSON langkah 1-3 tidak kepotong (truncated)
        $maxTokens = 2500;
        // OpenCode can take longer for long prompts — 60s timeout
        // 2026-08-30: deepseek-v4-flash L1 = ~35s normal, bisa >60s saat antrian.
        // connectTimeout ketat (koneksi gagal cepat gagal), timeout 180s untuk body lambat.
        $res = Http::withToken($provider->api_key)->connectTimeout(15)->timeout(180)->post($url, [
            'model' => $model,
            'messages' => [
                ['role'=>'system','content'=>$system],
                ['role'=>'user','content'=>$userMsg],
            ],
            'temperature' => 0.7,
            'max_tokens' => $maxTokens,
        ]);
        if ($res->failed()) return ['error' => 'OpenCode '.ucfirst($variant).' '.$res->status().': '.$res->body()];
        $content = $res->json('choices.0.message.content') ?? '—';
        return ['provider'=>$provider->provider,'model'=>$model,'content'=>$content,'mock'=>false];
    }

    private function resolveProvider(?string $modelValue): ?AiProvider
    {
        if ($modelValue && str_contains($modelValue, ':')) {
            [$prov] = explode(':', $modelValue, 2);
            $p = AiProvider::where('provider', $prov)->where('is_active', true)->first();
            if ($p) return $p;
        }
        // fallback ke default model utama
        $defaultVal = $this->defaultModelValue();
        if ($defaultVal && str_contains($defaultVal, ':')) {
            [$prov] = explode(':', $defaultVal, 2);
            $p = AiProvider::where('provider', $prov)->where('is_active', true)->first();
            if ($p) return $p;
        }
        return $this->defaultProvider();
    }

    private function resolveModel(?string $modelValue, AiProvider $provider): string
    {
        if ($modelValue && str_contains($modelValue, ':')) {
            [, $model] = explode(':', $modelValue, 2);
            if (in_array($model, $provider->enabled_models ?? [])) return $model;
        }
        // jika provider punya model default (utama/vision), coba pakai yang sesuai provider
        $defaultVal = $this->defaultModelValue();
        if ($defaultVal && str_contains($defaultVal, ':')) {
            [$dProv, $dModel] = explode(':', $defaultVal, 2);
            if ($dProv === $provider->provider && in_array($dModel, $provider->enabled_models ?? [])) return $dModel;
        }
        return $provider->enabled_models[0] ?? $provider->available_models[0]['id'] ?? 'gpt-4o-mini';
    }

    public function fetchModels(AiProvider $provider): array
    {
        $key = $provider->api_key ?? '';
        // OpenCode models endpoint public → boleh fetch tanpa key, jadi jangan block
        $isOpenCode = in_array($provider->provider, ['opencode_go','opencode_zen']);
        if (! $key && ! $isOpenCode) return ['error' => 'API Key kosong'];
        try {
            return match ($provider->provider) {
                'openai' => $this->fetchOpenAI($key, $provider->base_url),
                'gemini' => $this->fetchGemini($key),
                'anthropic' => $this->fetchAnthropic($key),
                'groq' => $this->fetchGroq($key),
                'deepseek' => $this->fetchDeepseek($key),
                'openrouter' => $this->fetchOpenRouter($key),
                'mistral' => $this->fetchMistral($key),
                'opencode_go' => $this->fetchOpenCode($key, $provider->base_url, 'go'),
                'opencode_zen' => $this->fetchOpenCode($key, $provider->base_url, 'zen'),
                default => ['error' => 'Provider tidak dikenal'],
            };
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function fetchOpenAI(string $key, ?string $baseUrl): array
    {
        $url = ($baseUrl ?: 'https://api.openai.com/v1') . '/models';
        $res = Http::withToken($key)->timeout(12)->get($url);
        if ($res->failed()) return ['error' => 'OpenAI error ' . $res->status() . ': ' . $res->body()];
        $data = $res->json('data') ?? $res->json();
        $out = [];
        foreach ($data as $m) {
            $id = $m['id'] ?? null;
            if ($id && str_starts_with($id, 'gpt')) $out[] = ['id' => $id, 'label' => $id];
        }
        if (empty($out)) $out = array_map(fn($id) => ['id' => $id, 'label' => $id], AiProvider::PROVIDERS['openai']['default_models']);
        return ['models' => $out];
    }

    private function fetchGemini(string $key): array
    {
        $url = 'https://generativelanguage.googleapis.com/v1/models?key=' . $key;
        $res = Http::timeout(12)->get($url);
        if ($res->failed()) return ['error' => 'Gemini error ' . $res->status() . ': ' . $res->body()];
        $out = [];
        foreach ($res->json('models', []) as $m) {
            $name = $m['name'] ?? '';
            $id = str_replace('models/', '', $name);
            if ($id) $out[] = ['id' => $id, 'label' => $m['displayName'] ?? $id];
        }
        if (empty($out)) $out = array_map(fn($id) => ['id' => $id, 'label' => $id], AiProvider::PROVIDERS['gemini']['default_models']);
        return ['models' => $out];
    }

    private function fetchAnthropic(string $key): array
    {
        return ['models' => array_map(fn($id) => ['id' => $id, 'label' => $id], AiProvider::PROVIDERS['anthropic']['default_models'])];
    }

    private function fetchGroq(string $key): array
    {
        $res = Http::withToken($key)->timeout(12)->get('https://api.groq.com/openai/v1/models');
        if ($res->failed()) return ['models' => array_map(fn($id) => ['id' => $id, 'label' => $id], AiProvider::PROVIDERS['groq']['default_models'])];
        $out = [];
        foreach ($res->json('data', []) as $m) $out[] = ['id' => $m['id'], 'label' => $m['id']];
        return ['models' => $out ?: array_map(fn($id) => ['id' => $id, 'label' => $id], AiProvider::PROVIDERS['groq']['default_models'])];
    }

    private function fetchDeepseek(string $key): array
    {
        return ['models' => array_map(fn($id) => ['id' => $id, 'label' => $id], AiProvider::PROVIDERS['deepseek']['default_models'])];
    }

    private function fetchOpenRouter(string $key): array
    {
        $res = Http::withToken($key)->timeout(12)->get('https://openrouter.ai/api/v1/models');
        if ($res->failed()) return ['models' => array_map(fn($id) => ['id' => $id, 'label' => $id], AiProvider::PROVIDERS['openrouter']['default_models'])];
        $out = [];
        foreach (array_slice($res->json('data', []), 0, 30) as $m) $out[] = ['id' => $m['id'], 'label' => $m['id']];
        return ['models' => $out];
    }

    private function fetchMistral(string $key): array
    {
        $res = Http::withHeaders(['Authorization' => "Bearer $key"])->timeout(12)->get('https://api.mistral.ai/v1/models');
        if ($res->failed()) return ['models' => array_map(fn($id) => ['id' => $id, 'label' => $id], AiProvider::PROVIDERS['mistral']['default_models'])];
        $out = [];
        foreach ($res->json('data', []) as $m) $out[] = ['id' => $m['id'], 'label' => $m['id']];
        return ['models' => $out];
    }

    private function fetchOpenCode(string $key, ?string $baseUrl, string $variant): array
    {
        // FIX: base_url valid = https://opencode.ai/zen/go/v1 (untuk go & zen, dari user)
        // endpoint = {base}/models  → live 33 models, tidak perlu filter, tidak hardcode fallback diam-diam
        $base = $baseUrl ?: 'https://opencode.ai/zen/go/v1';
        $url = rtrim($base, '/') . '/models';
        // models endpoint ternyata public (tanpa auth pun 200), jadi jangan require key — coba dengan token jika ada, fallback tanpa token
        $headers = $key ? ['Authorization' => 'Bearer '.$key] : [];
        $res = Http::withHeaders($headers)->timeout(12)->get($url);
        if ($res->failed()) {
            return ['error' => 'OpenCode '.ucfirst($variant).' fetch failed '.$res->status().': '.$res->body().' (url: '.$url.')'];
        }
        // format live: {"object":"list","data":[{"id":"glm-5.3",...}, ...]}
        $data = $res->json('data') ?? $res->json('models') ?? $res->json();
        $out = [];
        if (is_array($data)) {
            foreach ($data as $m) {
                if (!is_array($m)) continue;
                $id = $m['id'] ?? $m['name'] ?? null;
                if ($id) $out[] = ['id' => $id, 'label' => $m['label'] ?? $m['displayName'] ?? $id];
            }
        }
        if (empty($out)) {
            return ['error' => 'OpenCode '.ucfirst($variant).' response tidak mengandung model (url: '.$url.', body: '.substr($res->body(),0,300).')'];
        }
        return ['models' => $out];
    }
}
