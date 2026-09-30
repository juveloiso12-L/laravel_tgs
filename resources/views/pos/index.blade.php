@extends('pos.app')

@section('title', 'Kasir')

@section('content')
<div class="pos-container min-h-screen flex flex-col bg-gray-50">
    <!-- HEADER -->
    <header class="header bg-white shadow-sm border-b border-gray-200 px-4 py-3">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="store-name text-xl font-bold text-gray-800">TOKO RETAIL MAKMUR</div>
                <div class="store-info text-sm text-gray-500">
                    Jl. Contoh No. 123 • Jember • Telp. 0812-xxxx-xxxx
                </div>
            </div>
            <div class="transaction-info text-right md:text-left flex flex-col md:flex-row md:items-center md:justify-between gap-2 w-full md:w-auto">
                <div>
                    <div class="text-xs text-gray-500 uppercase">No. Transaksi</div>
                    <div class="transaction-number font-mono text-lg font-bold text-gray-800" id="transactionNumber">
                        {{ $transactionNumber ?? 'TRX-' . date('Ymd') . '-001' }}
                    </div>
                </div>
                <div class="text-sm text-gray-600" id="currentDate"></div>
                <div class="text-xs text-gray-400">Kasir: {{ Auth::user()->name }}</div>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="main flex-1 overflow-hidden">
        <div class="max-w-7xl mx-auto h-full p-4">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 h-full">
                <!-- LEFT PANEL - Product Search & Cart -->
                <section class="lg:col-span-7 h-full flex flex-col min-w-0">
                    <!-- SEARCH & CUSTOMER -->
                    <div class="card bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex-shrink-0">
                        <div class="card-header px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-semibold text-gray-800">Tambah Barang</h3>
                        </div>
                        <div class="card-body p-4 space-y-3">
                            <!-- Search Input -->
                            <div class="search-area flex gap-2">
                                <div class="search-input flex-1 relative">
                                    <input 
                                        type="text" 
                                        id="searchProduct" 
                                        placeholder="Cari nama barang / kode / barcode... (F2 untuk fokus)" 
                                        autocomplete="off"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        autofocus
                                    >
                                    <div class="product-results absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden" id="productResults"></div>
                                </div>
                                <button 
                                    type="button" 
                                    class="btn btn-primary px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"
                                    onclick="searchProduct()"
                                >
                                    Cari
                                </button>
                            </div>

                            <!-- Customer Selection -->
                            <div class="customer-area border-t border-gray-200 pt-3 space-y-3">
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pelanggan</label>
                                    <select 
                                        id="customerSelect"
                                        class="form-control w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    >
                                        <option value="">Umum (Walk-in)</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" data-type="{{ $customer->member_type }}" data-points="{{ $customer->points }}" @selected(($transaction->customer_id ?? null) === $customer->id)>
                                                {{ $customer->name }} ({{ ucfirst($customer->member_type) }}) - {{ $customer->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">No. Member / HP</label>
                                    <input 
                                        type="text" 
                                        id="customerPhone" 
                                        placeholder="Opsional - Scan kartu member atau ketik nomor HP"
                                        class="form-control w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CART -->
                    <div class="card bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex-1 flex flex-col min-h-0" style="margin-top:15px">
                        <div class="card-header px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">Keranjang Belanja</h3>
                            <span id="itemCount" class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded">0 Item</span>
                        </div>
                        <div class="table-wrapper flex-1 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-10">No</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Barang</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-24">Harga</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-28">Qty</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">Subtotal</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-10"></th>
                                    </tr>
                                </thead>
                                <tbody id="cartBody" class="divide-y divide-gray-100">
                                    <tr id="emptyRow">
                                        <td colspan="6" class="empty-cart px-3 py-8 text-center text-gray-400">
                                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                            </svg>
                                            <p class="mt-2">Keranjang masih kosong.</p>
                                            <p class="text-xs">Silakan cari atau scan barang.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- RIGHT PANEL - Payment Summary -->
                <aside class="lg:col-span-5 h-full flex flex-col min-w-0">
                    <div class="card bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex-1 flex flex-col min-h-0">
                        <div class="card-header px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-semibold text-gray-800">Ringkasan Pembayaran</h3>
                        </div>
                        <div class="card-body p-4 flex-1 overflow-y-auto">
                            <div class="payment-summary space-y-3">
                                <!-- Total Item -->
                                <div class="summary-row flex justify-between items-center">
                                    <span class="text-gray-600">Total Item</span>
                                    <strong id="totalQty" class="text-gray-800">0</strong>
                                </div>

                                <!-- Subtotal -->
                                <div class="summary-row flex justify-between items-center border-b border-gray-100 pb-3">
                                    <span class="text-gray-600">Subtotal</span>
                                    <strong id="subtotal" class="text-lg text-gray-800">Rp 0</strong>
                                </div>

                                <!-- Discount % -->
                                <div class="summary-row flex items-center gap-3">
                                    <span class="text-gray-600 w-28 flex-shrink-0">Diskon (%)</span>
                                    <input 
                                        type="number" 
                                        id="discountPercent" 
                                        value="{{ $transaction->discount_percent ?? 0 }}" 
                                        min="0" 
                                        max="100" 
                                        step="0.1"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-right"
                                        onchange="calculateTotal()"
                                    >
                                </div>

                                <!-- Discount Amount -->
                                <div class="summary-row flex items-center gap-3">
                                    <span class="text-gray-600 w-28 flex-shrink-0">Diskon (Rp)</span>
                                    <input 
                                        type="number" 
                                        id="discountAmount" 
                                        value="{{ $transaction->discount_amount ?? 0 }}" 
                                        min="0" 
                                        step="100"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-right"
                                        onchange="calculateTotal()"
                                    >
                                </div>

                                <!-- Tax -->
                                <div class="summary-row flex items-center gap-3">
                                    <span class="text-gray-600 w-28 flex-shrink-0">Pajak / PPN</span>
                                    <input 
                                        type="number" 
                                        id="tax" 
                                        value="{{ $transaction->tax ?? 0 }}" 
                                        min="0" 
                                        step="100"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-right"
                                        onchange="calculateTotal()"
                                    >
                                </div>

                                <!-- Other Fee -->
                                <div class="summary-row flex items-center gap-3 border-b border-gray-100 pb-3">
                                    <span class="text-gray-600 w-28 flex-shrink-0">Biaya Lain</span>
                                    <input 
                                        type="number" 
                                        id="otherFee" 
                                        value="{{ $transaction->other_fee ?? 0 }}" 
                                        min="0" 
                                        step="100"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-right"
                                        onchange="calculateTotal()"
                                    >
                                </div>

                                <!-- TOTAL -->
                                <div class="total-box bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="total-label text-sm text-blue-800 font-medium">TOTAL AKHIR</div>
                                    <div class="total-value text-3xl font-bold text-blue-900" id="grandTotal">Rp 0</div>
                                </div>
                            </div>

                            <!-- PAYMENT SECTION -->
                            <div class="payment-box mt-4 space-y-3 border-t border-gray-200 pt-4">
                                <div>
                                    <div class="payment-label text-sm font-medium text-gray-700 mb-1">Uang Dibayar</div>
                                    <input 
                                        type="number" 
                                        id="payment" 
                                        class="payment-input w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-right text-xl font-mono font-bold"
                                        placeholder="0"
                                        oninput="calculateChange()"
                                    >
                                    <div class="flex gap-2 mt-2" id="quickPayButtons"></div>
                                </div>

                                <!-- Payment Method -->
                                <div>
                                    <div class="payment-label text-sm font-medium text-gray-700 mb-2">Metode Pembayaran</div>
                                    <div class="payment-method flex flex-wrap gap-2">
                                        <button type="button" class="payment-method-btn active px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium" onclick="selectPayment(this)" data-method="tunai">Tunai</button>
                                        <button type="button" class="payment-method-btn px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200" onclick="selectPayment(this)" data-method="qris">QRIS</button>
                                        <button type="button" class="payment-method-btn px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200" onclick="selectPayment(this)" data-method="debit">Debit</button>
                                        <button type="button" class="payment-method-btn px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200" onclick="selectPayment(this)" data-method="kredit">Kredit</button>
                                        <button type="button" class="payment-method-btn px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200" onclick="selectPayment(this)" data-method="e_wallet">E-Wallet</button>
                                        <button type="button" class="payment-method-btn px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200" onclick="selectPayment(this)" data-method="transfer">Transfer</button>
                                    </div>
                                    <input type="hidden" id="paymentMethod" value="tunai">
                                </div>

                                <!-- CHANGE -->
                                <div class="change-box bg-green-50 border border-green-200 rounded-lg p-4" id="changeBox">
                                    <div class="change-label text-sm text-green-800 font-medium">KEMBALIAN</div>
                                    <div class="change-value text-2xl font-bold text-green-900" id="change">Rp 0</div>
                                </div>
                            </div>

                            <!-- ACTION BUTTONS -->
                            <div class="action-area mt-4 space-y-2 border-t border-gray-200 pt-4">
                                <button 
                                    type="button" 
                                    class="btn btn-warning w-full py-3 px-4 bg-yellow-600 text-white rounded-lg font-medium hover:bg-yellow-700 transition-colors"
                                    onclick="holdTransaction()"
                                >
                                    Tahan Transaksi (F3)
                                </button>
                                <button 
                                    type="button" 
                                    class="btn btn-danger w-full py-3 px-4 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors"
                                    onclick="cancelTransaction()"
                                >
                                    Batal Transaksi (ESC)
                                </button>
                                <button 
                                    type="button" 
                                    class="btn btn-success btn-pay w-full py-4 px-4 bg-green-600 text-white rounded-lg font-bold text-lg hover:bg-green-700 transition-colors shadow-lg"
                                    onclick="processPayment()"
                                >
                                    BAYAR & CETAK (F4)
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</div>

<!-- Hidden inputs for transaction data -->
<input type="hidden" id="heldTransactionId" value="{{ $transaction->id ?? '' }}">
<input type="hidden" id="isResuming" value="{{ isset($transaction) ? 'true' : 'false' }}">
@endsection

@push('scripts')
<script type="application/json" id="posConfig">
{!! json_encode([
    'searchUrl' => route('api.products.search'),
    'storeUrl' => route('transaksi.store'),
    'holdUrl' => route('transaksi.hold'),
    'heldUrl' => route('pos.held'),
    'receiptUrl' => route('receipt.thermal', ['transaction' => '__ID__']),
    'completeUrl' => isset($transaction) ? route('transaksi.complete', $transaction) : null,
    'cancelUrl' => isset($transaction) ? route('transaksi.cancel', $transaction) : null,
    'posUrl' => route('pos.index'),
    'transactionId' => $transaction->id ?? null,
    'initialItems' => $initialItems ?? [],
    'csrfToken' => csrf_token(),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
@endpush