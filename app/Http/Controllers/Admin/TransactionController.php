<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Transaction::with(['user','tier','approver'])->latest();
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        $transactions = $query->paginate(12)->withQueryString();

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only('status'),
        ]);
    }
}
