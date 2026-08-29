<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
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
        $providers = AiProvider::orderBy('is_default', 'desc')->orderBy('provider')->get();
        $availableProviders = collect(AiProvider::PROVIDERS)->map(fn($v,$k) => ['value'=>$k,'label'=>$v['label']])->values();
        $activeModels = (new AiService())->availableModels();

        return Inertia::render('Admin/Settings/AiProviders', [
            'providers' => $providers->map(fn($p) => [
                'id' => $p->id,
                'provider' => $p->provider,
                'label' => $p->label,
                'masked_key' => $p->maskedKey(),
                'has_key' => !empty($p->api_key),
                'base_url' => $p->base_url,
                'is_active' => $p->is_active,
                'is_default' => $p->is_default,
                'available_models' => $p->available_models,
                'enabled_models' => $p->enabled_models,
                'last_fetched_at' => $p->last_fetched_at,
                'created_at' => $p->created_at,
            ]),
            'availableProviders' => $availableProviders,
            'activeModels' => $activeModels,
            'defaultModelsMap' => AiProvider::PROVIDERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required','string', Rule::in(array_keys(AiProvider::PROVIDERS)), Rule::unique('ai_providers','provider')],
            'api_key' => ['required','string','min:10','max:500'],
            'base_url' => ['nullable','url','max:255'],
            'enabled_models' => ['nullable','array','max:30'],
            'enabled_models.*' => ['string','max:100'],
            'is_active' => ['boolean'],
        ]);

        $label = AiProvider::PROVIDERS[$data['provider']]['label'] ?? $data['provider'];
        $isFirst = AiProvider::count() === 0;

        // base_url default khusus opencode dari user: https://opencode.ai/zen/go/v1
        $defaultBase = AiProvider::PROVIDERS[$data['provider']]['base_url'] ?? null;
        $baseUrl = $data['base_url'] ?? $defaultBase;

        $provider = AiProvider::create([
            'provider' => $data['provider'],
            'label' => $label,
            'api_key' => $data['api_key'],
            'base_url' => $baseUrl,
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $isFirst,
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
                    // jika enabled_models masih default hardcode, ganti ke fetched (biar langsung live)
                    'enabled_models' => $data['enabled_models'] ?? array_column(array_slice($fetched['models'],0,5), 'id'),
                    'last_fetched_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // diamkan, tetap pakai default_models
        }

        activity()->causedBy(auth()->user())->log('Tambah AI provider '.$provider->provider);

        return back()->with('success', 'Provider '.$label.' ditambahkan.');
    }

    public function update(Request $request, AiProvider $aiProvider): RedirectResponse
    {
        $data = $request->validate([
            'api_key' => ['nullable','string','min:10','max:500'],
            'base_url' => ['nullable','url','max:255'],
            'enabled_models' => ['nullable','array','max:30'],
            'enabled_models.*' => ['string','max:100'],
            'is_active' => ['boolean'],
        ]);

        if (!empty($data['api_key'])) {
            $aiProvider->api_key = $data['api_key'];
        }
        if (array_key_exists('base_url', $data)) $aiProvider->base_url = $data['base_url'];
        if (array_key_exists('enabled_models', $data)) $aiProvider->enabled_models = $data['enabled_models'];
        if (array_key_exists('is_active', $data)) $aiProvider->is_active = $data['is_active'];
        $aiProvider->save();

        activity()->causedBy(auth()->user())->performedOn($aiProvider)->log('Update AI provider '.$aiProvider->provider);

        return back()->with('success', 'Provider '.$aiProvider->label.' diperbarui.');
    }

    public function destroy(AiProvider $aiProvider): RedirectResponse
    {
        $label = $aiProvider->label;
        $wasDefault = $aiProvider->is_default;
        $aiProvider->delete();
        if ($wasDefault && $next = AiProvider::first()) {
            $next->update(['is_default' => true]);
        }
        activity()->causedBy(auth()->user())->log('Hapus AI provider '.$label);
        return back()->with('success', 'Provider '.$label.' dihapus.');
    }

    public function setDefault(AiProvider $aiProvider): RedirectResponse
    {
        DB::transaction(function () use ($aiProvider) {
            AiProvider::query()->update(['is_default' => false]);
            $aiProvider->update(['is_default' => true, 'is_active' => true]);
        });
        return back()->with('success', $aiProvider->label.' jadi default provider.');
    }

    public function toggle(AiProvider $aiProvider): RedirectResponse
    {
        $aiProvider->update(['is_active' => ! $aiProvider->is_active]);
        if (! $aiProvider->is_active && $aiProvider->is_default) {
            $aiProvider->update(['is_default' => false]);
            if ($next = AiProvider::where('is_active', true)->first()) $next->update(['is_default' => true]);
        }
        return back()->with('success', $aiProvider->label.' ' . ($aiProvider->is_active ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    public function fetchModels(Request $request, AiProvider $aiProvider)
    {
        $request->validate([
            'api_key' => ['nullable','string'],
        ]);
        // if api_key provided in request, temporarily use it without saving
        $tempKey = $request->input('api_key');
        $origKey = $aiProvider->api_key;
        if ($tempKey) $aiProvider->api_key = $tempKey;

        $service = new AiService();
        $result = $service->fetchModels($aiProvider);

        // restore if temp
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
