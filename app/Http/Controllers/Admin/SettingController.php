<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();
        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'groups' => $settings->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['required','array'],
            'settings.*.key' => ['required','string'],
            'settings.*.value' => ['nullable','string'],
        ]);

        foreach ($request->input('settings') as $item) {
            $row = Setting::where('key', $item['key'])->first();
            if (!$row) continue;
            $val = $item['value'] ?? '';
            if ($row->is_encrypted) $val = encrypt($val);
            $row->update(['value' => $val]);
        }

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
