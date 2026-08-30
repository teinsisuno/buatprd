<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\Setting;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class AiProviderController extends Controller
{
    public function index(): Response
    {
        $providers = AiProvider::orderBy('provider')->get();
        $availableProviders = collect(AiProvider::PROVIDERS)->map(fn($v,$k) => ['value'=>$k,'label'=>$v['label']])->values();
        $activeModels = (new AiService())->availableModels();

        // NEW: 2 defaults level MODEL
        $defaultModel = Setting::get('ai_default_model', AiProvider::defaultModelValue());
        $defaultVisionModel = Setting::get('ai_default_vision_model', AiProvider::defaultVisionModelValue());
        // Jika belum ada di settings, pastikan tetap ada nilai
        if (! $defaultModel) $defaultModel = $activeModels[0]['value'] ?? null;
        if (! $defaultVisionModel) $defaultVisionModel = $defaultModel;

        return Inertia::render('Admin/Settings/AiProviders', [
            'providers' => $providers->map(fn($p) => [
                'id' => $p->id,
                'provider' => $p->provider,
                'label' => $p->label,
                'masked_key' => $p->maskedKey(),
                'has_key' => !empty($p->api_key),
                'base_url' => $p->base_url,
                'is_active' => $p->is_active,
                'is_default' => $p->is_default, // legacy, tetap dikirim tapi tidak dipakai utama
                'available_models' => $p->available_models,
                'enabled_models' => $p->enabled_models,
                'last_fetched_at' => $p->last_fetched_at,
                'created_at' => $p->created_at,
            ]),
            'availableProviders' => $availableProviders,
            'activeModels' => $activeModels,
            'defaultModelsMap' => AiProvider::PROVIDERS,
            'defaultModel' => $defaultModel,
            'defaultVisionModel' => $defaultVisionModel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required','string', Rule::in(array_keys(AiProvider::PROVIDERS)), Rule::unique('ai_providers','provider')],
            'api_key' => ['required','string','min:10','max:500'],
            'base_url' => ['nullable','url','max:255'],
            'enabled_models' => ['nullable','array','max:100'],
            'enabled_models.*' => ['string','max:100'],
            'is_active' => ['boolean'],
        ]);

        $label = AiProvider::PROVIDERS[$data['provider']]['label'] ?? $data['provider'];

        $defaultBase = AiProvider::PROVIDERS[$data['provider']]['base_url'] ?? null;
        $baseUrl = $data['base_url'] ?? $defaultBase;

        $provider = AiProvider::create([
            'provider' => $data['provider'],
            'label' => $label,
            'api_key' => $data['api_key'],
            'base_url' => $baseUrl,
            'is_active' => $data['is_active'] ?? true,
            'is_default' => false, // legacy tidak dipakai — default sekarang level model di settings
            'available_models' => array_map(fn($id) => ['id'=>$id,'label'=>$id], AiProvider::PROVIDERS[$data['provider']]['default_models'] ?? []),
            'enabled_models' => $data['enabled_models'] ?? (AiProvider::PROVIDERS[$data['provider']]['default_models'] ?? []),
        ]);

        // auto fetch live agar tidak hardcode — override available_models dengan hasil real API
        try {
            $service = new AiService();
            $fetched = $service->fetchModels($provider);
            if (!isset($fetched['error']) && !empty($fetched['models'])) {
                $provider->update([
                    'available_models' => $fetched['models'],
                    'enabled_models' => $data['enabled_models'] ?? array_column(array_slice($fetched['models'],0,5), 'id'),
                    'last_fetched_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
        }

        // Jika ini provider pertama & belum ada default model di settings → set otomatis
        $currentDefault = Setting::get('ai_default_model');
        if (! $currentDefault && !empty($provider->enabled_models)) {
            Setting::set('ai_default_model', $provider->provider . ':' . $provider->enabled_models[0], 'ai', 'AI Utama (default wizard)', 'text');
            Setting::set('ai_default_vision_model', $provider->provider . ':' . $provider->enabled_models[0], 'ai', 'AI Vision (default untuk gambar)', 'text');
        }

        activity()->causedBy(auth()->user())->log('Tambah AI provider '.$provider->provider);

        return back()->with('success', 'Provider '.$label.' ditambahkan.');
    }

    public function update(Request $request, AiProvider $aiProvider): RedirectResponse
    {
        $data = $request->validate([
            'api_key' => ['nullable','string','min:10','max:500'],
            'base_url' => ['nullable','url','max:255'],
            'enabled_models' => ['nullable','array','max:100'],
            'enabled_models.*' => ['string','max:100'],
            'is_active' => ['boolean'],
        ]);

        if (!empty($data['api_key'])) {
            $aiProvider->api_key = $data['api_key'];
        }
        if (array_key_exists('base_url', $data)) $aiProvider->base_url = $data['base_url'];
        if (array_key_exists('enabled_models', $data)) {
            $aiProvider->enabled_models = $data['enabled_models'];
            // Jika default model sekarang jadi tidak enabled → fallback otomatis
            $def = Setting::get('ai_default_model');
            $defV = Setting::get('ai_default_vision_model');
            if ($def && str_starts_with($def, $aiProvider->provider . ':')) {
                $model = explode(':', $def, 2)[1] ?? '';
                if (! in_array($model, $data['enabled_models'] ?? [])) {
                    // pindahkan ke model pertama yang masih enabled dari provider manapun
                    $active = AiProvider::active()->whereNotNull('enabled_models')->get();
                    foreach ($active as $p) {
                        if (!empty($p->enabled_models)) {
                            Setting::set('ai_default_model', $p->provider . ':' . $p->enabled_models[0], 'ai', 'AI Utama (default wizard)', 'text');
                            break;
                        }
                    }
                }
            }
            if ($defV && str_starts_with($defV, $aiProvider->provider . ':')) {
                $model = explode(':', $defV, 2)[1] ?? '';
                if (! in_array($model, $data['enabled_models'] ?? [])) {
                    $active = AiProvider::active()->whereNotNull('enabled_models')->get();
                    foreach ($active as $p) {
                        if (!empty($p->enabled_models)) {
                            Setting::set('ai_default_vision_model', $p->provider . ':' . $p->enabled_models[0], 'ai', 'AI Vision (default untuk gambar)', 'text');
                            break;
                        }
                    }
                }
            }
        }
        if (array_key_exists('is_active', $data)) $aiProvider->is_active = $data['is_active'];
        $aiProvider->save();

        activity()->causedBy(auth()->user())->performedOn($aiProvider)->log('Update AI provider '.$aiProvider->provider);

        return back()->with('success', 'Provider '.$aiProvider->label.' diperbarui.');
    }

    public function destroy(AiProvider $aiProvider): RedirectResponse
    {
        $label = $aiProvider->label;
        $prov = $aiProvider->provider;
        $aiProvider->delete();

        // Jika default model berasal dari provider yang dihapus → ganti ke yang aktif pertama
        $def = Setting::get('ai_default_model');
        $defV = Setting::get('ai_default_vision_model');
        if ($def && str_starts_with($def, $prov . ':')) {
            $next = AiProvider::active()->whereNotNull('enabled_models')->first();
            if ($next && !empty($next->enabled_models)) {
                Setting::set('ai_default_model', $next->provider . ':' . $next->enabled_models[0], 'ai', 'AI Utama (default wizard)', 'text');
            }
        }
        if ($defV && str_starts_with($defV, $prov . ':')) {
            $next = AiProvider::active()->whereNotNull('enabled_models')->first();
            if ($next && !empty($next->enabled_models)) {
                Setting::set('ai_default_vision_model', $next->provider . ':' . $next->enabled_models[0], 'ai', 'AI Vision (default untuk gambar)', 'text');
            }
        }

        activity()->causedBy(auth()->user())->log('Hapus AI provider '.$label);
        return back()->with('success', 'Provider '.$label.' dihapus.');
    }

    // LEGACY: provider-level default — tetap ada tapi sekarang redirect ke model-level
    public function setDefault(AiProvider $aiProvider): RedirectResponse
    {
        DB::transaction(function () use ($aiProvider) {
            AiProvider::query()->update(['is_default' => false]);
            $aiProvider->update(['is_default' => true, 'is_active' => true]);
        });
        // sinkronkan juga ke settings model default (ambil enabled pertama)
        if (!empty($aiProvider->enabled_models)) {
            Setting::set('ai_default_model', $aiProvider->provider . ':' . $aiProvider->enabled_models[0], 'ai', 'AI Utama (default wizard)', 'text');
        }
        return back()->with('success', $aiProvider->label.' jadi default provider (legacy). Default model Utama juga diperbarui.');
    }

    /** NEW: set default MODEL (Utama atau Vision) — level model, bukan provider */
    public function setDefaultModel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'value' => ['required','string','max:150'], // format provider:model
            'type' => ['required','string', Rule::in(['utama','vision'])],
        ]);
        $value = $data['value'];
        $type = $data['type'];

        if (! str_contains($value, ':')) {
            return back()->with('error', 'Format model harus provider:model');
        }
        [$prov, $model] = explode(':', $value, 2);
        $p = AiProvider::where('provider', $prov)->where('is_active', true)->first();
        if (! $p) return back()->with('error', 'Provider tidak aktif');
        if (! in_array($model, $p->enabled_models ?? [])) return back()->with('error', 'Model tidak di-checked (tidak aktif untuk member)');

        $key = $type === 'vision' ? 'ai_default_vision_model' : 'ai_default_model';
        $label = $type === 'vision' ? 'AI Vision (default untuk gambar)' : 'AI Utama (default wizard)';
        Setting::set($key, $value, 'ai', $label, 'text');

        $typeLabel = $type === 'vision' ? 'AI Vision' : 'AI Utama';
        return back()->with('success', $typeLabel.' diubah ke '.$value);
    }

    public function toggle(AiProvider $aiProvider): RedirectResponse
    {
        $aiProvider->update(['is_active' => ! $aiProvider->is_active]);
        if (! $aiProvider->is_active && $aiProvider->is_default) {
            $aiProvider->update(['is_default' => false]);
            if ($next = AiProvider::where('is_active', true)->first()) $next->update(['is_default' => true]);
        }
        // Jika provider dinonaktifkan dan dia pemilik default model → fallback
        if (! $aiProvider->is_active) {
            $def = Setting::get('ai_default_model');
            $defV = Setting::get('ai_default_vision_model');
            if ($def && str_starts_with($def, $aiProvider->provider . ':')) {
                $next = AiProvider::active()->whereNotNull('enabled_models')->first();
                if ($next && !empty($next->enabled_models)) Setting::set('ai_default_model', $next->provider . ':' . $next->enabled_models[0], 'ai', 'AI Utama (default wizard)', 'text');
            }
            if ($defV && str_starts_with($defV, $aiProvider->provider . ':')) {
                $next = AiProvider::active()->whereNotNull('enabled_models')->first();
                if ($next && !empty($next->enabled_models)) Setting::set('ai_default_vision_model', $next->provider . ':' . $next->enabled_models[0], 'ai', 'AI Vision (default untuk gambar)', 'text');
            }
        }
        return back()->with('success', $aiProvider->label.' ' . ($aiProvider->is_active ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    public function fetchModels(Request $request, AiProvider $aiProvider)
    {
        $request->validate([
            'api_key' => ['nullable','string'],
        ]);
        $tempKey = $request->input('api_key');
        $origKey = $aiProvider->api_key;
        if ($tempKey) $aiProvider->api_key = $tempKey;

        $service = new AiService();
        $result = $service->fetchModels($aiProvider);

        if ($tempKey) $aiProvider->api_key = $origKey;

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        $aiProvider->update([
            'available_models' => $result['models'],
            'enabled_models' => $aiProvider->enabled_models ?? array_column($result['models'], 'id'),
            'last_fetched_at' => now(),
        ]);

        return back()->with('success', 'Berhasil fetch '.count($result['models']).' model dari '.$aiProvider->label);
    }

    public function fetchPreview(Request $request)
    {
        $request->validate([
            'provider' => ['required', Rule::in(array_keys(AiProvider::PROVIDERS))],
            'api_key' => ['required','string','min:10'],
            'base_url' => ['nullable','url'],
        ]);
        $tmp = new AiProvider([
            'provider' => $request->input('provider'),
            'label' => AiProvider::PROVIDERS[$request->input('provider')]['label'],
            'api_key' => $request->input('api_key'),
            'base_url' => $request->input('base_url'),
        ]);
        $service = new AiService();
        $result = $service->fetchModels($tmp);
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 422);
        }
        return response()->json($result);
    }

    public function test(AiProvider $aiProvider)
    {
        $service = new AiService();
        $result = $service->testConnection($aiProvider);
        if (!$result['ok']) {
            return back()->with('error', 'Test gagal: ' . $result['error']);
        }
        return back()->with('success', $result['message']);
    }
}
