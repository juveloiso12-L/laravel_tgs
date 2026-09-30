<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        // Generate transaction number
        $transactionNumber = 'TRX-'.date('Ymd').'-'.str_pad(
            Transaction::whereDate('created_at', today())->count() + 1,
            3,
            '0',
            STR_PAD_LEFT
        );

        $initialItems = [];

        return view('pos.index', compact('categories', 'customers', 'transactionNumber', 'initialItems'));
    }

    public function heldTransactions(): View
    {
        $heldTransactions = Transaction::with(['customer', 'details.product'])
            ->where('status', 'held')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('pos.held', compact('heldTransactions'));
    }

    public function resumeTransaction(Transaction $transaction): View|RedirectResponse
    {
        if ($transaction->status !== 'held') {
            return redirect()->route('pos.index')->with('error', 'Transaksi tidak dapat dilanjutkan.');
        }

        if ($transaction->user_id !== Auth::id()) {
            return redirect()->route('pos.index')->with('error', 'Anda tidak memiliki akses ke transaksi ini.');
        }

        $transaction->load('details.product');

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $initialItems = $transaction->details
            ->filter(fn ($detail) => $detail->product !== null)
            ->map(fn ($detail) => [
                'id' => $detail->product_id,
                'code' => $detail->product_code,
                'name' => $detail->product_name,
                'price' => (float) $detail->price,
                'stock' => $detail->product->stock,
                'unit' => $detail->product->unit,
                'qty' => $detail->qty,
            ])
            ->values();

        // Use the existing transaction number for held transactions
        $transactionNumber = $transaction->transaction_number;

        return view('pos.index', compact('categories', 'customers', 'transactionNumber', 'transaction', 'initialItems'))
            ->with('resumeTransaction', $transaction);
    }
}
