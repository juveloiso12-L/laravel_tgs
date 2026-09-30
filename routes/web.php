<?php

use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('pos.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// POS Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // POS Main Page
    Route::get('/kasir', [PosController::class, 'index'])->name('pos.index');
    Route::get('/kasir/transaksi-ditahan', [PosController::class, 'heldTransactions'])->name('pos.held');
    Route::get('/kasir/lanjutkan/{transaction}', [PosController::class, 'resumeTransaction'])->name('pos.resume');

    // API Routes for POS
    Route::prefix('api')->group(function () {
        Route::get('/products/search', [ProductController::class, 'search'])->name('api.products.search');
        Route::get('/products/barcode/{barcode}', [ProductController::class, 'getByBarcode'])->name('api.products.barcode');
    });

    // Transaction Routes
    Route::post('/transaksi', [TransactionController::class, 'store'])->name('transaksi.store');
    Route::post('/transaksi/tahan', [TransactionController::class, 'hold'])->name('transaksi.hold');
    Route::post('/transaksi/{transaction}/selesaikan', [TransactionController::class, 'completeHeld'])->name('transaksi.complete');
    Route::delete('/transaksi/{transaction}/batal', [TransactionController::class, 'cancel'])->name('transaksi.cancel');
    
    // Transaction History
    Route::get('/transaksi/riwayat', [TransactionController::class, 'history'])->name('transaksi.history');
    Route::get('/transaksi/{transaction}', [TransactionController::class, 'show'])->name('transaksi.show');

    // Receipt Printing
    Route::get('/transaksi/{transaction}/struk', [ReceiptController::class, 'print'])->name('receipt.print');
    Route::get('/transaksi/{transaction}/struk-thermal', [ReceiptController::class, 'printThermal'])->name('receipt.thermal');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';