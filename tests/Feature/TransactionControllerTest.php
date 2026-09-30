<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_uses_current_product_price_and_reduces_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 10);

        $response = $this->actingAs($user)->postJson(route('transaksi.store'), [
            'items' => [[
                'product_id' => $product->id,
                'qty' => 2,
                'price' => 1,
            ]],
            'paid_amount' => 10000,
            'payment_method' => 'tunai',
        ]);

        $response->assertOk()->assertJsonPath('data.grand_total', 7000);
        $this->assertDatabaseHas('transactions', [
            'subtotal' => 7000,
            'grand_total' => 7000,
            'paid_amount' => 10000,
            'change_amount' => 3000,
        ]);
        $this->assertDatabaseHas('transaction_details', [
            'product_id' => $product->id,
            'price' => 3500,
            'qty' => 2,
            'subtotal' => 7000,
        ]);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_checkout_rejects_combined_quantity_that_exceeds_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 5);

        $response = $this->actingAs($user)->postJson(route('transaksi.store'), [
            'items' => [
                ['product_id' => $product->id, 'qty' => 4, 'price' => 3500],
                ['product_id' => $product->id, 'qty' => 4, 'price' => 3500],
            ],
            'paid_amount' => 30000,
            'payment_method' => 'tunai',
        ]);

        $response->assertUnprocessable()->assertJsonPath('errors.items.0', 'Stok Produk Uji tidak mencukupi. Tersedia: 5');
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_rejects_insufficient_payment_without_changing_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 5);

        $response = $this->actingAs($user)->postJson(route('transaksi.store'), [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'paid_amount' => 3499,
            'payment_method' => 'tunai',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.paid_amount.0', 'Uang pembayaran kurang dari total belanja.');
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cancelling_a_held_transaction_does_not_restore_unreserved_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 10);

        $heldResponse = $this->actingAs($user)->postJson(route('transaksi.hold'), [
            'items' => [['product_id' => $product->id, 'qty' => 2, 'price' => 3500]],
        ]);
        $transaction = Transaction::findOrFail($heldResponse->json('data.transaction_id'));

        $this->deleteJson(route('transaksi.cancel', $transaction))->assertOk();

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame('cancelled', $transaction->fresh()->status);
    }

    public function test_completing_a_held_transaction_rejects_combined_quantity_that_exceeds_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 5);
        $transaction = Transaction::create([
            'transaction_number' => 'TRX-TEST-HELD',
            'user_id' => $user->id,
            'subtotal' => 28000,
            'grand_total' => 28000,
            'payment_method' => 'tunai',
            'status' => 'held',
        ]);

        foreach ([1, 2] as $index) {
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'product_code' => $product->code,
                'product_name' => $product->name,
                'price' => $product->price,
                'qty' => 4,
                'subtotal' => 14000,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('transaksi.complete', $transaction), [
            'paid_amount' => 28000,
            'payment_method' => 'tunai',
        ]);

        $response->assertUnprocessable();
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('held', $transaction->fresh()->status);
    }

    public function test_resuming_and_holding_again_updates_the_same_transaction(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 10);

        $heldResponse = $this->actingAs($user)->postJson(route('transaksi.hold'), [
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ]);
        $transactionId = $heldResponse->json('data.transaction_id');

        $this->get(route('pos.held'))->assertOk()->assertSee($transactionId);
        $this->get(route('pos.resume', $transactionId))->assertOk()->assertSee('Produk Uji');

        $this->postJson(route('transaksi.hold'), [
            'transaction_id' => $transactionId,
            'items' => [['product_id' => $product->id, 'qty' => 3]],
        ])->assertOk()->assertJsonPath('data.transaction_id', $transactionId);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transaction_details', [
            'transaction_id' => $transactionId,
            'product_id' => $product->id,
            'qty' => 3,
            'subtotal' => 10500,
        ]);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_completing_a_resumed_cart_recalculates_totals_and_saves_the_changes(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 10);

        $heldResponse = $this->actingAs($user)->postJson(route('transaksi.hold'), [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ]);
        $transaction = Transaction::findOrFail($heldResponse->json('data.transaction_id'));

        $response = $this->postJson(route('transaksi.complete', $transaction), [
            'items' => [['product_id' => $product->id, 'qty' => 2]],
            'discount_amount' => 1000,
            'paid_amount' => 10000,
            'payment_method' => 'tunai',
        ]);

        $response->assertOk()->assertJsonPath('data.grand_total', 6000)->assertJsonPath('data.change_amount', 4000);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'subtotal' => 7000,
            'grand_total' => 6000,
            'paid_amount' => 10000,
            'change_amount' => 4000,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('transaction_details', [
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'qty' => 2,
            'subtotal' => 7000,
        ]);
        $this->assertSame(8, $product->fresh()->stock);

        $this->get(route('receipt.thermal', $transaction))
            ->assertOk()
            ->assertSee('Produk Uji')
            ->assertSee('Rp 6.000');
    }

    public function test_kasir_screen_and_product_search_use_the_database_catalog(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(stock: 10);

        $screen = $this->actingAs($user)->get(route('pos.index'));
        $screen->assertOk()->assertSee('id="posConfig"', false);

        $this->getJson(route('api.products.search', ['q' => $product->code]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.price', 3500)
            ->assertJsonPath('data.0.stock', 10);
    }

    public function test_cashier_cannot_view_another_users_transaction_or_receipt(): void
    {
        $cashier = User::factory()->create();
        $transactionOwner = User::factory()->create();
        $transaction = Transaction::create([
            'transaction_number' => 'TRX-OTHER-CASHIER',
            'user_id' => $transactionOwner->id,
            'subtotal' => 0,
            'grand_total' => 0,
            'payment_method' => 'tunai',
            'status' => 'completed',
        ]);

        $this->actingAs($cashier)
            ->get(route('transaksi.show', $transaction))
            ->assertForbidden();
        $this->get(route('receipt.thermal', $transaction))->assertForbidden();
    }

    private function createProduct(int $stock): Product
    {
        return Product::create([
            'code' => 'TEST001',
            'barcode' => 'TEST000001',
            'name' => 'Produk Uji',
            'price' => 3500,
            'stock' => $stock,
            'min_stock' => 1,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
    }
}
