<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MembershipTier;
use App\Models\Transaction;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $membership = $user->activeMembership()->with('tier')->first() ?? $user->membership()->with('tier')->latest()->first();
        $tiers = \Illuminate\Support\Facades\Cache::remember('membership_tiers', 3600, fn() => 
            MembershipTier::where('is_active', true)->orderBy('sort_order')->get()
        );
        $transactions = $user->transactions()->with(['tier','coupon'])->latest()->paginate(10)->withQueryString();

        return Inertia::render('Member/Billing/Index', [
            'membership' => $membership ? [
                'id' => $membership->id,
                'tier' => $membership->tier,
                'status' => $membership->status,
                'started_at' => $membership->started_at,
                'expired_at' => $membership->expired_at,
                'ai_quota_total' => $membership->ai_quota_total,
                'ai_quota_used' => $membership->ai_quota_used,
                'credit_balance' => $membership->credit_balance,
                'is_expired' => $membership->isExpired(),
            ] : null,
            'tiers' => $tiers,
            'transactions' => $transactions,
            'payment' => config('buatprd.payment'),
        ]);
    }

    public function checkout(Request $request, MembershipTier $tier): Response
    {
        if (! $tier->is_active) abort(404);
        if ($tier->slug === 'default') {
            return Inertia::render('Member/Billing/Checkout', [
                'tier' => $tier,
                'payment' => config('buatprd.payment'),
                'error' => 'Paket default gratis, tidak perlu bayar.',
            ]);
        }
        // coupon preview
        $coupon = null;
        if ($code = $request->query('coupon')) {
            $coupon = Coupon::where('code', strtoupper($code))->first();
        }

        return Inertia::render('Member/Billing/Checkout', [
            'tier' => $tier,
            'payment' => config('buatprd.payment'),
            'coupon' => $coupon && $coupon->isValid() ? $coupon : null,
            'coupon_error' => $coupon && ! $coupon->isValid() ? 'Kupon tidak valid / expired.' : null,
        ]);
    }

    public function store(Request $request, MembershipTier $tier): RedirectResponse
    {
        if (! $tier->is_active) abort(404);
        if ($tier->slug === 'default') {
            return back()->with('error', 'Paket default tidak perlu transaksi.');
        }

        $request->validate([
            'proof' => ['required','file','mimes:jpg,jpeg,png,webp','max:2048'],
            'coupon_code' => ['nullable','string','max:20'],
        ]);

        $coupon = null;
        $discount = 0;
        if ($code = $request->input('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper(trim($code)))->first();
            if (! $coupon || ! $coupon->isValid()) {
                return back()->with('error', 'Kode kupon tidak valid atau sudah habis.');
            }
            if ($coupon->applicable_tier_id && $coupon->applicable_tier_id !== $tier->id) {
                return back()->with('error', 'Kupon tidak berlaku untuk paket ini.');
            }
            // check per-user usage limit
            $usedByUser = Transaction::where('user_id', $user->id)
                ->where('coupon_id', $coupon->id)
                ->where('status', '!=', 'rejected')
                ->count();
            if ($usedByUser >= $coupon->max_uses_per_user) {
                return back()->with('error', "Kupon {$coupon->code} sudah pernah kamu pakai sebanyak {$coupon->max_uses_per_user}x.");
            }
            $discount = $coupon->discountAmount($tier->price);
        }

        $user = $request->user();
        // prevent duplicate pending for same tier
        $hasPending = $user->transactions()->where('type','subscription')->where('status','pending')->exists();
        if ($hasPending) {
            return back()->with('error', 'Kamu masih punya transaksi pending. Tunggu approve admin dulu.');
        }

        $file = $request->file('proof');
        $path = $file->store('private/proofs', 'local');

        Transaction::create([
            'user_id' => $user->id,
            'tier_id' => $tier->id,
            'coupon_id' => $coupon?->id,
            'coupon_code' => $coupon?->code,
            'discount_amount' => $discount,
            'type' => 'subscription',
            'amount' => $tier->price,
            'payment_method' => 'transfer',
            'proof_path' => $path,
            'proof_original_name' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        return redirect()->route('member.billing.index')->with('success', 'Bukti berhasil diupload. Menunggu verifikasi admin (1x24 jam).');
    }

    public function invoice(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) abort(403);
        if ($transaction->status !== 'approved') abort(404, 'Invoice hanya untuk transaksi approved.');
        return app(\App\Http\Controllers\InvoiceController::class)->memberInvoice($request, $transaction);
    }
}
