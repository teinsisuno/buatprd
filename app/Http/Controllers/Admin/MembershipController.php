<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipTier;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class MembershipController extends Controller
{
    public function index(): Response
    {
        $tiers = MembershipTier::orderBy('sort_order')->get();
        $memberships = UserMembership::with(['user','tier'])->latest()->paginate(12);

        return Inertia::render('Admin/Memberships/Index', [
            'tiers' => $tiers,
            'memberships' => $memberships,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:50'],
            'slug' => ['required','string','max:50','alpha_dash', Rule::unique('membership_tiers','slug')],
            'price' => ['required','integer','min:0','max:10000000'],
            'duration_days' => ['required','integer','min:1','max:365'],
            'limits.max_projects' => ['required','integer','min:1','max:99999'],
            'limits.max_ai_per_month' => ['required','integer','min:1','max:100000'],
            'limits.can_export_pdf' => ['required','boolean'],
            'limits.can_mermaid' => ['required','boolean'],
            'limits.can_share' => ['required','boolean'],
            'limits.can_template_premium' => ['required','boolean'],
            'features' => ['required','array','min:1','max:10'],
            'features.*' => ['required','string','max:100'],
            'is_active' => ['required','boolean'],
            'sort_order' => ['nullable','integer','min:0','max:100'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? (MembershipTier::max('sort_order') + 1);

        MembershipTier::create($data);

        activity()->causedBy(auth()->user())->log('Membuat paket '.$data['name']);

        return back()->with('success', 'Paket berhasil dibuat.');
    }

    public function update(Request $request, MembershipTier $tier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:50'],
            'slug' => ['required','string','max:50','alpha_dash', Rule::unique('membership_tiers','slug')->ignore($tier->id)],
            'price' => ['required','integer','min:0','max:10000000'],
            'duration_days' => ['required','integer','min:1','max:365'],
            'limits.max_projects' => ['required','integer','min:1','max:99999'],
            'limits.max_ai_per_month' => ['required','integer','min:1','max:100000'],
            'limits.can_export_pdf' => ['required','boolean'],
            'limits.can_mermaid' => ['required','boolean'],
            'limits.can_share' => ['required','boolean'],
            'limits.can_template_premium' => ['required','boolean'],
            'features' => ['required','array','min:1','max:10'],
            'features.*' => ['required','string','max:100'],
            'is_active' => ['required','boolean'],
            'sort_order' => ['nullable','integer','min:0','max:100'],
        ]);

        $tier->update($data);

        activity()->causedBy(auth()->user())->performedOn($tier)->log('Update paket '.$tier->name);

        return back()->with('success', 'Paket '.$tier->name.' berhasil diperbarui.');
    }

    public function destroy(MembershipTier $tier): RedirectResponse
    {
        if ($tier->slug === 'default') {
            return back()->with('error', 'Paket default tidak bisa dihapus.');
        }
        if ($tier->memberships()->where('status','active')->exists()) {
            return back()->with('error', 'Paket masih dipakai member aktif, nonaktifkan saja.');
        }
        $name = $tier->name;
        $tier->delete();
        activity()->causedBy(auth()->user())->log('Hapus paket '.$name);
        return back()->with('success', 'Paket '.$name.' dihapus.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required','array','min:1'],
            'order.*' => ['required','integer','exists:membership_tiers,id'],
        ]);
        foreach ($request->input('order') as $idx => $id) {
            MembershipTier::where('id', $id)->update(['sort_order' => $idx + 1]);
        }
        return back()->with('success', 'Urutan paket diperbarui.');
    }
}
