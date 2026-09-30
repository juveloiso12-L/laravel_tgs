@extends('pos.app')

@section('title', 'Transaksi Ditahan')

@section('content')
<main class="min-h-screen bg-gray-50 px-4 py-6">
    <div class="mx-auto max-w-5xl">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold uppercase text-blue-700">Kasir</p>
                <h1 class="text-2xl font-bold text-gray-900">Transaksi Ditahan</h1>
            </div>
            <a href="{{ route('pos.index') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Transaksi Baru
            </a>
        </header>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            @if($heldTransactions->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-gray-500">Belum ada transaksi yang ditahan.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">No. Transaksi</th>
                                <th class="px-5 py-3">Pelanggan</th>
                                <th class="px-5 py-3">Waktu</th>
                                <th class="px-5 py-3 text-right">Total</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($heldTransactions as $transaction)
                                <tr>
                                    <td class="px-5 py-4 font-mono font-semibold text-gray-800">{{ $transaction->transaction_number }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ $transaction->customer?->name ?? 'Umum' }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4 text-right font-semibold text-gray-800">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('pos.resume', $transaction) }}" class="font-semibold text-blue-700 hover:text-blue-900">Lanjutkan</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</main>
@endsection