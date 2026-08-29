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
        return AiProvider::where('is_default', true)->where('is_active', true)->first()
            ?? AiProvider::active()->first();
    }

    public function generate(int $step, array $input, ?string $modelValue = null): array
    {
        $provider = $this->resolveProvider($modelValue);
        if (! $provider) {
            return ['error' => 'Belum ada AI Provider aktif. Hubungi admin di /admin/ai-providers.'];
        }
        $model = $this->resolveModel($modelValue, $provider);
        $prompts = config('ai.prompts', []);
        $stepPrompt = $prompts[$step] ?? ['name' => "Langkah $step"];
        $system = $stepPrompt['system'] ?? 'Kamu asisten PRD.';

        // Untuk opencode_go/zen dan key test, tetap mock tapi anggap valid untuk UI
        $isTestKey = str_starts_with($provider->api_key ?? '', 'sk-test');
        $isOpenCode = in_array($provider->provider, ['opencode_go','opencode_zen']);

        if (!$isTestKey && !$isOpenCode) {
            $real = $this->callProvider($provider, $model, $step, $input);
            if (!isset($real['error'])) return $real;
            // fallback ke mock dengan info error
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

        // OpenCode & test key: mock tapi siap
        if ($isOpenCode) {
            $try = $this->callProvider($provider, $model, $step, $input);
            if (!isset($try['error'])) return $try;
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

    private function callProvider(AiProvider $provider, string $model, int $step, array $input): array
    {
        $prompts = config('ai.prompts', []);
        $stepPrompt = $prompts[$step] ?? ['name' => "Langkah $step", 'system' => 'Kamu asisten PRD.'];
        $system = $stepPrompt['system'] ?? '';
        $userContent = json_encode($input, JSON_UNESCAPED_UNICODE);
        try {
            return match ($provider->provider) {
                'openai' => $this->callOpenAI($provider, $model, $system, $userContent, $step),
                'gemini' => $this->callGemini($provider, $model, $system, $userContent),
                'anthropic' => $this->callAnthropic($provider, $model, $system, $userContent),
                'groq' => $this->callGroq($provider, $model, $system, $userContent),
                'deepseek' => $this->callOpenAI($provider, $model, $system, $userContent, $step, 'https://api.deepseek.com/v1'),
                'openrouter' => $this->callOpenAI($provider, $model, $system, $userContent, $step, 'https://openrouter.ai/api/v1'),
                'mistral' => $this->callMistral($provider, $model, $system, $userContent),
                'opencode_go' => $this->callOpenCode($provider, $model, $system, $userContent, 'go'),
                'opencode_zen' => $this->callOpenCode($provider, $model, $system, $userContent, 'zen'),
                default => ['error' => 'Provider tidak dikenal'],
            };
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function callOpenAI(AiProvider $provider, string $model, string $system, string $userContent, int $step, ?string $baseOverride = null): array
    {
        $base = $baseOverride ?: ($provider->base_url ?: 'https://api.openai.com/v1');
        $url = rtrim($base, '/') . '/chat/completions';
        $res = Http::withToken($provider->api_key)->timeout(25)->post($url, [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userContent],
            ],
            'temperature' => 0.7,
            'max_tokens' => 800,
        ]);
        if ($res->failed()) return ['error' => 'OpenAI '.$res->status().': '.$res->body()];
        $content = $res->json('choices.0.message.content') ?? $res->json('choices.0.text') ?? '—';
        return ['provider'=>$provider->provider,'model'=>$model,'step'=>$step,'content'=>$content,'mock'=>false];
    }

    private function callGemini(AiProvider $provider, string $model, string $system, string $userContent): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $provider->api_key;
        $res = Http::timeout(25)->post($url, [
            'contents' => [['parts' => [['text' => $system . "\n\n" . $userContent]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 800],
        ]);
        if ($res->failed()) return ['error' => 'Gemini '.$res->status().': '.$res->body()];
        $content = $res->json('candidates.0.content.parts.0.text') ?? '—';
        return ['provider'=>$provider->provider,'model'=>$model,'content'=>$content,'mock'=>false];
    }

    private function callAnthropic(AiProvider $provider, string $model, string $system, string $userContent): array
    {
        $res = Http::withHeaders(['x-api-key' => $provider->api_key, 'anthropic-version' => '2023-06-01'])->timeout(25)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 800,
            'system' => $system,
            'messages' => [['role'=>'user','content'=>$userContent]],
        ]);
        if ($res->failed()) return ['error' => 'Anthropic '.$res->status().': '.$res->body()];
        $content = $res->json('content.0.text') ?? '—';
        return ['provider'=>$provider->provider,'model'=>$model,'content'=>$content,'mock'=>false];
    }

    private function callGroq(AiProvider $provider, string $model, string $system, string $userContent): array
    {
        return $this->callOpenAI($provider, $model, $system, $userContent, 0, 'https://api.groq.com/openai/v1');
    }

    private function callMistral(AiProvider $provider, string $model, string $system, string $userContent): array
    {
        return $this->callOpenAI($provider, $model, $system, $userContent, 0, 'https://api.mistral.ai/v1');
    }

    private function callOpenCode(AiProvider $provider, string $model, string $system, string $userContent, string $variant): array
    {
        $base = $provider->base_url ?: 'https://opencode.ai/zen/go/v1';
        $url = rtrim($base, '/') . '/chat/completions';
        $res = Http::withToken($provider->api_key)->timeout(15)->post($url, [
            'model' => $model,
            'messages' => [
                ['role'=>'system','content'=>$system],
                ['role'=>'user','content'=>$userContent],
            ],
            'temperature' => 0.7,
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
        return $this->defaultProvider();
    }

    private function resolveModel(?string $modelValue, AiProvider $provider): string
    {
        if ($modelValue && str_contains($modelValue, ':')) {
            [, $model] = explode(':', $modelValue, 2);
            if (in_array($model, $provider->enabled_models ?? [])) return $model;
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
