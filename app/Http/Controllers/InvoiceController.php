<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function memberInvoice(Request $request, Transaction $transaction): Response
    {
        $this->authorizeInvoice($request, $transaction);
        return $this->renderInvoice($transaction);
    }

    public function adminInvoice(Request $request, Transaction $transaction): Response
    {
        // admin already via role middleware, just render
        return $this->renderInvoice($transaction);
    }

    private function authorizeInvoice(Request $request, Transaction $transaction): void
    {
        if (! $request->user()->hasAnyRole(['superadmin','admin']) && $transaction->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    private function renderInvoice(Transaction $transaction): Response
    {
        $transaction->load(['user','tier','coupon']);
        $net = $transaction->amount - ($transaction->discount_amount ?? 0);

        $html = view('invoices.transaction', [
            'trx' => $transaction,
            'net' => $net,
            'payment' => config('buatprd.payment'),
        ])->render();

        // Try dompdf if available
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            return $pdf->download('invoice-'.$transaction->id.'.pdf');
        }

        // Fallback: return HTML as PDF-like (browser print)
        return response($html)->header('Content-Type', 'text/html');
    }

    public function proof(Request $request, Transaction $transaction)
    {
        $this->authorizeInvoice($request, $transaction);
        if (! $transaction->proof_path || ! Storage::disk('local')->exists($transaction->proof_path)) {
            abort(404, 'Bukti tidak ditemukan');
        }
        $path = Storage::disk('local')->path($transaction->proof_path);
        return response()->file($path);
    }
}
