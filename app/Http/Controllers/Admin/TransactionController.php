<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\UserMembership;
use App\Models\MembershipTier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Transaction::with(['user','tier','approver','coupon'])->latest();
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        $transactions = $query->paginate(12)->withQueryString();

        $stats = [
            'pending' => Transaction::where('status','pending')->count(),
            'approved' => Transaction::where('status','approved')->count(),
            'rejected' => Transaction::where('status','rejected')->count(),
            'revenue' => Transaction::where('status','approved')->sum(DB::raw('amount - discount_amount')),
        ];

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only('status','type'),
            'stats' => $stats,
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        $transaction->load(['user.activeMembership.tier','tier','approver','coupon']);
        return Inertia::render('Admin/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    public function approve(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Transaksi sudah diproses.');
        }

        $request->validate(['admin_note' => ['nullable','string','max:500']]);

        DB::transaction(function () use ($request, $transaction) {
            $transaction->update([
                'status' => 'approved',
                'admin_note' => $request->input('admin_note'),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $user = $transaction->user;

            if ($transaction->type === 'subscription' && $transaction->tier_id) {
                $tier = MembershipTier::find($transaction->tier_id);
                if ($tier) {
                    $membership = $user->activeMembership ?? $user->membership()->latest()->first();
                    if (! $membership) {
                        $membership = new UserMembership(['user_id' => $user->id]);
                    }
                    $membership->fill([
                        'tier_id' => $tier->id,
                        'status' => 'active',
                        'started_at' => now(),
                        'expired_at' => now()->addDays($tier->duration_days),
                        'ai_quota_total' => $tier->limits['max_ai_per_month'] ?? 10,
                        'ai_quota_used' => 0,
                    ]);
                    // keep credit_balance as is
                    $membership->user_id = $user->id;
                    $membership->save();
                }
            } elseif ($transaction->type === 'topup') {
                $creditMap = [25000 => 50, 50000 => 120, 100000 => 270];
                $credits = $creditMap[$transaction->amount] ?? (int) ($transaction->amount / 500); // fallback
                $membership = $user->activeMembership ?? $user->membership()->latest()->first();
                if ($membership) {
                    $membership->increment('credit_balance', $credits);
                } else {
                    // create default membership with credits
                    $defaultTier = MembershipTier::where('slug','default')->first();
                    UserMembership::create([
                        'user_id' => $user->id,
                        'tier_id' => $defaultTier?->id,
                        'status' => 'active',
                        'started_at' => now(),
                        'expired_at' => now()->addDays(30),
                        'ai_quota_total' => $defaultTier?->limits['max_ai_per_month'] ?? 10,
                        'ai_quota_used' => 0,
                        'credit_balance' => $credits,
                    ]);
                }
            }

            // coupon usage
            if ($transaction->coupon_id) {
                $transaction->coupon?->increment('used_count');
            }

            activity()
                ->performedOn($transaction)
                ->causedBy(auth()->user())
                ->withProperties(['type' => $transaction->type, 'amount' => $transaction->amount])
                ->log('Approve transaksi #'.$transaction->id.' ('.$transaction->type.')');
        });

        return back()->with('success', 'Transaksi #'.$transaction->id.' berhasil di-approve. Membership user diperbarui.');
    }

    public function reject(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Transaksi sudah diproses.');
        }
        $request->validate([
            'admin_note' => ['required','string','max:500'],
        ]);

        $transaction->update([
            'status' => 'rejected',
            'admin_note' => $request->input('admin_note'),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        activity()
            ->performedOn($transaction)
            ->causedBy(auth()->user())
            ->log('Reject transaksi #'.$transaction->id.' alasan: '.$request->input('admin_note'));

        return back()->with('success', 'Transaksi #'.$transaction->id.' ditolak.');
    }

    public function proof(Transaction $transaction): BinaryFileResponse|RedirectResponse
    {
        if (! $transaction->proof_path || ! Storage::disk('local')->exists($transaction->proof_path)) {
            return back()->with('error', 'Bukti transfer tidak ditemukan.');
        }
        $path = Storage::disk('local')->path($transaction->proof_path);
        return response()->file($path);
    }
}
