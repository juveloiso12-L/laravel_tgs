<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaction->transaction_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        body { width: 72mm; margin: 0 auto; color: #111; font: 12px/1.4 monospace; }
        h1, p { margin: 0; }
        .center { text-align: center; }
        .muted { color: #555; }
        .rule { border-top: 1px dashed #111; margin: 8px 0; }
        .line { display: flex; justify-content: space-between; gap: 8px; }
        .product { margin: 6px 0; }
        .product-name { overflow-wrap: anywhere; }
        .totals .line { margin: 3px 0; }
        .grand-total { font-size: 15px; font-weight: 700; }
        @media screen {
            body { margin: 24px auto; padding: 16px; background: #fff; box-shadow: 0 1px 8px #0002; }
        }
    </style>
</head>
<body>
    <header class="center">
        <h1>TOKO RETAIL MAKMUR</h1>
        <p class="muted">Jl. Contoh No. 123 · Jember</p>
    </header>
    <div class="rule"></div>
    <div class="line"><span>No.</span><span>{{ $transaction->transaction_number }}</span></div>
    <div class="line"><span>Waktu</span><span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
    <div class="line"><span>Kasir</span><span>{{ $transaction->user->name }}</span></div>
    <div class="line"><span>Pelanggan</span><span>{{ $transaction->customer?->name ?? 'Umum' }}</span></div>
    <div class="rule"></div>

    @foreach($transaction->details as $detail)
        <div class="product">
            <div class="product-name">{{ $detail->product_name }}</div>
            <div class="line">
                <span>{{ $detail->qty }} x Rp {{ number_format($detail->price, 0, ',', '.') }}</span>
                <span>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
            </div>
        </div>
    @endforeach

    <div class="rule"></div>
    <div class="totals">
        <div class="line"><span>Subtotal</span><span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span></div>
        @if($transaction->discount_percent > 0 || $transaction->discount_amount > 0)
            <div class="line"><span>Diskon</span><span>- Rp {{ number_format(($transaction->subtotal * $transaction->discount_percent / 100) + $transaction->discount_amount, 0, ',', '.') }}</span></div>
        @endif
        @if($transaction->tax > 0)
            <div class="line"><span>Pajak</span><span>Rp {{ number_format($transaction->tax, 0, ',', '.') }}</span></div>
        @endif
        @if($transaction->other_fee > 0)
            <div class="line"><span>Biaya lain</span><span>Rp {{ number_format($transaction->other_fee, 0, ',', '.') }}</span></div>
        @endif
        <div class="line grand-total"><span>TOTAL</span><span>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span></div>
        <div class="line"><span>Bayar ({{ strtoupper($transaction->payment_method) }})</span><span>Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span></div>
        <div class="line"><span>Kembalian</span><span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span></div>
    </div>
    <div class="rule"></div>
    <p class="center">Terima kasih telah berbelanja</p>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>