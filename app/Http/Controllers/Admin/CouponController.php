<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\MembershipTier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(Request $request): Response
    {
        $coupons = Coupon::with('tier')->latest()->paginate(12);
        $tiers = MembershipTier::orderBy('sort_order')->get(['id','name','slug']);
        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons,
            'tiers' => $tiers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required','string','max:20','alpha_dash', Rule::unique('coupons','code')],
            'name' => ['nullable','string','max:100'],
            'discount_percent' => ['required','integer','min:1','max:100'],
            'max_uses' => ['nullable','integer','min:1','max:10000'],
            'max_uses_per_user' => ['required','integer','min:1','max:10'],
            'applicable_tier_id' => ['nullable','exists:membership_tiers,id'],
            'is_active' => ['required','boolean'],
            'expires_at' => ['nullable','date','after:now'],
        ]);
        $data['code'] = strtoupper($data['code']);
        Coupon::create($data);
        return back()->with('success', 'Kupon dibuat.');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required','string','max:20','alpha_dash', Rule::unique('coupons','code')->ignore($coupon->id)],
            'name' => ['nullable','string','max:100'],
            'discount_percent' => ['required','integer','min:1','max:100'],
            'max_uses' => ['nullable','integer','min:1','max:10000'],
            'max_uses_per_user' => ['required','integer','min:1','max:10'],
            'applicable_tier_id' => ['nullable','exists:membership_tiers,id'],
            'is_active' => ['required','boolean'],
            'expires_at' => ['nullable','date'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $coupon->update($data);
        return back()->with('success', 'Kupon diperbarui.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();
        return back()->with('success', 'Kupon dihapus.');
    }
}
