<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'other_fee' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:tunai,qris,debit,kredit,e_wallet,transfer',
            'notes' => 'nullable|string',
        ]);

        $transactionNumber = 'TRX-'.date('Ymd').'-'.str_pad(
            Transaction::whereDate('created_at', today())->count() + 1,
            3,
            '0',
            STR_PAD_LEFT
        );

        try {
            DB::beginTransaction();

            // Calculate totals
            $subtotal = 0;
            $items = [];

            $quantities = collect($validated['items'])
                ->groupBy('product_id')
                ->map(fn ($items) => $items->sum('qty'));

            foreach ($quantities as $productId => $qty) {
                $qty = (int) $qty;
                $product = Product::query()
                    ->whereKey($productId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Salah satu produk sudah tidak tersedia.',
                    ]);
                }

                // Check stock
                if ($product->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak mencukupi. Tersedia: {$product->stock}",
                    ]);
                }

                $price = (float) $product->price;
                $discount = 0;
                $itemSubtotal = $price * $qty;
                $subtotal += $itemSubtotal;

                $items[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'price' => $price,
                    'qty' => $qty,
                    'discount' => $discount,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $discountPercent = $validated['discount_percent'] ?? 0;
            $discountAmount = $validated['discount_amount'] ?? 0;
            $tax = $validated['tax'] ?? 0;
            $otherFee = $validated['other_fee'] ?? 0;

            $percentDiscount = $subtotal * ($discountPercent / 100);
            $totalDiscount = $percentDiscount + $discountAmount;
            $grandTotal = max(0, $subtotal - $totalDiscount + $tax + $otherFee);

            $paidAmount = $validated['paid_amount'];
            $changeAmount = max(0, $paidAmount - $grandTotal);

            if ($paidAmount < $grandTotal) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Uang pembayaran kurang dari total belanja.',
                ]);
            }

            // Create transaction
            $transaction = Transaction::create([
                'transaction_number' => $transactionNumber,
                'user_id' => Auth::id(),
                'customer_id' => $validated['customer_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'tax' => $tax,
                'other_fee' => $otherFee,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create transaction details and update stock
            foreach ($items as $item) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    ...$item,
                ]);

                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->decrement('stock', (int) $item['qty']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'grand_total' => $grandTotal,
                    'paid_amount' => $paidAmount,
                    'change_amount' => $changeAmount,
                ],
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function hold(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'other_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'transaction_id' => 'nullable|integer',
        ]);

        $heldTransaction = isset($validated['transaction_id'])
            ? Transaction::find($validated['transaction_id'])
            : null;

        if (isset($validated['transaction_id']) && (! $heldTransaction || $heldTransaction->user_id !== Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke transaksi ini.',
            ], 403);
        }

        if ($heldTransaction && $heldTransaction->status !== 'held') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak dapat ditahan.',
            ], 422);
        }

        $transactionNumber = $heldTransaction?->transaction_number ?? 'TRX-'.date('Ymd').'-'.str_pad(
            Transaction::whereDate('created_at', today())->count() + 1,
            3,
            '0',
            STR_PAD_LEFT
        );

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $items = [];

            $quantities = collect($validated['items'])
                ->groupBy('product_id')
                ->map(fn ($items) => $items->sum('qty'));

            foreach ($quantities as $productId => $qty) {
                $qty = (int) $qty;
                $product = Product::query()
                    ->whereKey($productId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Salah satu produk sudah tidak tersedia.',
                    ]);
                }

                if ($product->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak mencukupi. Tersedia: {$product->stock}",
                    ]);
                }

                $price = (float) $product->price;
                $discount = 0;

                $itemSubtotal = $price * $qty;
                $subtotal += $itemSubtotal;

                $items[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'price' => $price,
                    'qty' => $qty,
                    'discount' => $discount,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $discountPercent = $validated['discount_percent'] ?? 0;
            $discountAmount = $validated['discount_amount'] ?? 0;
            $tax = $validated['tax'] ?? 0;
            $otherFee = $validated['other_fee'] ?? 0;

            $percentDiscount = $subtotal * ($discountPercent / 100);
            $totalDiscount = $percentDiscount + $discountAmount;
            $grandTotal = max(0, $subtotal - $totalDiscount + $tax + $otherFee);

            $transaction = $heldTransaction ?? new Transaction;
            $transaction->fill([
                'transaction_number' => $transactionNumber,
                'user_id' => Auth::id(),
                'customer_id' => $validated['customer_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'tax' => $tax,
                'other_fee' => $otherFee,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'change_amount' => 0,
                'payment_method' => 'tunai',
                'status' => 'held',
                'notes' => $validated['notes'] ?? null,
            ])->save();

            $transaction->details()->delete();

            foreach ($items as $item) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    ...$item,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil ditahan',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                ],
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke transaksi ini.',
            ], 403);
        }

        if (! in_array($transaction->status, ['pending', 'held'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak dapat dibatalkan.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $transaction->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibatalkan',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function completeHeld(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke transaksi ini.',
            ], 403);
        }

        if ($transaction->status !== 'held') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak dapat dilanjutkan.',
            ], 422);
        }

        $validated = $request->validate([
            'items' => 'sometimes|required|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.qty' => 'required_with:items|integer|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'other_fee' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:tunai,qris,debit,kredit,e_wallet,transfer',
        ]);

        DB::beginTransaction();
        try {
            $transaction = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->status !== 'held') {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi tidak dapat dilanjutkan.',
                ]);
            }

            $existingDetails = $transaction->details->whereNotNull('product_id')->groupBy('product_id');
            $items = [];
            $subtotal = (float) $transaction->subtotal;

            if (isset($validated['items'])) {
                $quantities = collect($validated['items'])
                    ->groupBy('product_id')
                    ->map(fn ($productItems) => $productItems->sum('qty'));
                $subtotal = 0;
                foreach ($quantities as $productId => $qty) {
                    $qty = (int) $qty;
                    $product = Product::query()
                        ->whereKey($productId)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if (! $product) {
                        throw ValidationException::withMessages([
                            'items' => 'Salah satu produk sudah tidak tersedia.',
                        ]);
                    }

                    $price = (float) ($existingDetails->get($productId)?->first()?->price ?? $product->price);
                    $itemSubtotal = $price * $qty;
                    $subtotal += $itemSubtotal;
                    $items[] = [
                        'product_id' => $product->id,
                        'product_code' => $product->code,
                        'product_name' => $product->name,
                        'price' => $price,
                        'qty' => $qty,
                        'discount' => 0,
                        'subtotal' => $itemSubtotal,
                    ];
                }
            } else {
                $quantities = $existingDetails->map(fn ($details) => $details->sum('qty'));
            }

            $discountPercent = $validated['discount_percent'] ?? (float) $transaction->discount_percent;
            $discountAmount = $validated['discount_amount'] ?? (float) $transaction->discount_amount;
            $tax = $validated['tax'] ?? (float) $transaction->tax;
            $otherFee = $validated['other_fee'] ?? (float) $transaction->other_fee;
            $grandTotal = max(0, $subtotal - ($subtotal * $discountPercent / 100) - $discountAmount + $tax + $otherFee);
            $paidAmount = (float) $validated['paid_amount'];

            if ($paidAmount < $grandTotal) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Uang pembayaran kurang dari total belanja.',
                ]);
            }

            $changeAmount = max(0, $paidAmount - $grandTotal);
            $products = [];

            foreach ($quantities as $productId => $qty) {
                $qty = (int) $qty;
                $product = Product::query()
                    ->whereKey($productId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $product || $product->stock < $qty) {
                    $productName = $product?->name ?? 'Salah satu produk';
                    $availableStock = $product?->stock ?? 0;

                    throw ValidationException::withMessages([
                        'items' => "Stok {$productName} tidak mencukupi. Tersedia: {$availableStock}",
                    ]);
                }

                $products[$productId] = $product;
            }

            if (isset($validated['items'])) {
                $transaction->details()->delete();
                foreach ($items as $item) {
                    $transaction->details()->create($item);
                }
            }

            foreach ($products as $productId => $product) {
                $product->decrement('stock', $quantities[$productId]);
            }

            $transaction->update([
                'customer_id' => array_key_exists('customer_id', $validated) ? $validated['customer_id'] : $transaction->customer_id,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'tax' => $tax,
                'other_fee' => $otherFee,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diselesaikan',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'grand_total' => $grandTotal,
                    'paid_amount' => $paidAmount,
                    'change_amount' => $changeAmount,
                ],
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function history()
    {
        $transactions = Transaction::with(['customer', 'user', 'details'])
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->latest()
            ->paginate(20);

        return view('transactions.history', compact('transactions'));
    }

    public function show(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        $transaction->load(['customer', 'user', 'details.product']);

        return view('transactions.show', compact('transaction'));
    }
}
