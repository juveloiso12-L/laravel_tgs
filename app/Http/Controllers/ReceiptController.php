<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function print(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        $transaction->load(['customer', 'user', 'details.product']);

        return view('receipt.print', compact('transaction'));
    }

    public function printThermal(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        $transaction->load(['customer', 'user', 'details.product']);

        return view('receipt.thermal', compact('transaction'));
    }
}
