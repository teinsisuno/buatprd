<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $fillable = ['provider','label','api_key','base_url','is_active','is_default','available_models','enabled_models','config','last_fetched_at'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'available_models' => 'array',
            'enabled_models' => 'array',
            'config' => 'array',
            'last_fetched_at' => 'datetime',
        ];
    }

    public const PROVIDERS = [
        'openai' => ['label' => 'OpenAI', 'default_models' => ['gpt-4o-mini','gpt-4o','gpt-4o-mini-transcribe']],
        'gemini' => ['label' => 'Google Gemini', 'default_models' => ['gemini-1.5-flash','gemini-1.5-pro','gemini-2.0-flash']],
        'anthropic' => ['label' => 'Anthropic Claude', 'default_models' => ['claude-3-5-sonnet-20241022','claude-3-haiku-20240307']],
        'groq' => ['label' => 'Groq', 'default_models' => ['llama-3.1-8b-instant','mixtral-8x7b-32768']],
        'deepseek' => ['label' => 'DeepSeek', 'default_models' => ['deepseek-chat','deepseek-reasoner']],
        'openrouter' => ['label' => 'OpenRouter', 'default_models' => ['openai/gpt-4o-mini','google/gemini-flash-1.5']],
        'mistral' => ['label' => 'Mistral', 'default_models' => ['mistral-large-latest','mistral-small-latest']],
        'opencode_go' => ['label' => 'OpenCode Go', 'base_url' => 'https://opencode.ai/zen/go/v1', 'default_models' => ['minimax-m3','minimax-m2.7','minimax-m2.5','kimi-k3','kimi-k2.7-code','kimi-k2.6','longcat-2.0','kimi-k2.5','glm-5.2','glm-5.3-flash','glm-5.3','glm-5.1','glm-5','deepseek-v4-pro','deepseek-v4-flash','deepseek-v4-flash-vision-exp','qwen3.7-max','qwen3.8-max','qwen3.8-flash','qwen3.7-plus','qwen3.6-plus','qwen3.5-plus','mimo-v2-pro','mimo-v2-omni','mimo-v2.5-pro','mimo-v2.5','hy4-preview','hy3','hy3-preview','gpt-5.6-luna','grok-4.5','grok-4.6','muse-spark-1.2-contributor']],
        'opencode_zen' => ['label' => 'OpenCode Zen', 'base_url' => 'https://opencode.ai/zen/go/v1', 'default_models' => ['minimax-m3','minimax-m2.7','minimax-m2.5','kimi-k3','kimi-k2.7-code','kimi-k2.6','longcat-2.0','kimi-k2.5','glm-5.2','glm-5.3-flash','glm-5.3','glm-5.1','glm-5','deepseek-v4-pro','deepseek-v4-flash','deepseek-v4-flash-vision-exp','qwen3.7-max','qwen3.8-max','qwen3.8-flash','qwen3.7-plus','qwen3.6-plus','qwen3.5-plus','mimo-v2-pro','mimo-v2-omni','mimo-v2.5-pro','mimo-v2.5','hy4-preview','hy3','hy3-preview','gpt-5.6-luna','grok-4.5','grok-4.6','muse-spark-1.2-contributor']],
    ];

    public function maskedKey(): string
    {
        $k = $this->api_key;
        if (! $k) return '—';
        $len = strlen($k);
        if ($len <= 8) return str_repeat('*', $len);
        return substr($k, 0, 4) . '****...****' . substr($k, -4);
    }

    public function enabledModelsList(): array
    {
        return $this->enabled_models ?? [];
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeDefault($q) { return $q->where('is_default', true); }

    /**
     * NEW: 2 defaults level MODEL (bukan provider) — disimpan di settings
     * key: ai_default_model, ai_default_vision_model — format "provider:model"
     */
    public static function defaultModelValue(): ?string
    {
        $val = Setting::get('ai_default_model');
        if ($val && is_string($val) && str_contains($val, ':')) return $val;
        // fallback: model pertama dari provider aktif yang ada enabled_models
        $first = static::active()->whereNotNull('enabled_models')->first();
        if ($first && !empty($first->enabled_models)) return $first->provider . ':' . $first->enabled_models[0];
        return null;
    }

    public static function defaultVisionModelValue(): ?string
    {
        $val = Setting::get('ai_default_vision_model');
        if ($val && is_string($val) && str_contains($val, ':')) return $val;
        // fallback ke default utama kalau vision belum di-set
        return static::defaultModelValue();
    }

    public static function activeModelsGrouped(): array
    {
        $providers = static::active()->whereNotNull('enabled_models')->get();
        $default = static::defaultModelValue();
        $defaultVision = static::defaultVisionModelValue();
        $out = [];
        foreach ($providers as $p) {
            foreach (($p->enabled_models ?? []) as $m) {
                $value = $p->provider . ':' . $m;
                $isDefault = $value === $default;
                $isVisionDefault = $value === $defaultVision;
                $tags = [];
                if ($isDefault) $tags[] = 'Utama';
                if ($isVisionDefault) $tags[] = 'Vision';
                $tagStr = $tags ? ' (' . implode(' + ', $tags) . ')' : '';
                $out[] = [
                    'provider' => $p->provider,
                    'label' => $p->label,
                    'model' => $m,
                    'value' => $value,
                    'display' => $p->label . ' — ' . $m . $tagStr,
                    'is_default' => $isDefault,
                    'is_vision_default' => $isVisionDefault,
                ];
            }
        }
        return $out;
    }

    /** Helper untuk cek apakah value ada di enabled_models aktif */
    public static function isModelEnabled(string $value): bool
    {
        if (! str_contains($value, ':')) return false;
        [$prov, $model] = explode(':', $value, 2);
        $p = static::where('provider', $prov)->where('is_active', true)->first();
        if (! $p) return false;
        return in_array($model, $p->enabled_models ?? []);
    }
}
