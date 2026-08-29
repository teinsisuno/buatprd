<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TopUpController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $membership = $user->activeMembership()->with('tier')->first();
        $transactions = $user->transactions()->where('type','topup')->with('tier')->latest()->paginate(10)->withQueryString();

        return Inertia::render('Member/TopUp/Index', [
            'membership' => $membership ? [
                'credit_balance' => $membership->credit_balance,
                'tier' => $membership->tier,
            ] : null,
            'options' => config('buatprd.topup_options'),
            'payment' => config('buatprd.payment'),
            'transactions' => $transactions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $options = collect(config('buatprd.topup_options'))->pluck('amount')->toArray();

        $request->validate([
            'amount' => ['required','integer', 'in:'.implode(',', $options)],
            'proof' => ['required','file','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);

        $user = $request->user();
        $hasPending = $user->transactions()->where('type','topup')->where('status','pending')->exists();
        if ($hasPending) {
            return back()->with('error', 'Masih ada top up pending, tunggu approve dulu.');
        }

        $file = $request->file('proof');
        $path = $file->store('proofs','local');

        Transaction::create([
            'user_id' => $user->id,
            'tier_id' => null,
            'type' => 'topup',
            'amount' => $request->input('amount'),
            'payment_method' => 'transfer',
            'proof_path' => $path,
            'proof_original_name' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        return redirect()->route('member.topup.index')->with('success', 'Bukti top up berhasil diupload. Menunggu verifikasi admin.');
    }
}
