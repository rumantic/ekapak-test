<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_order_creation_decreases_stock_and_calculates_total(): void
    {
        $customer = Customer::factory()->create();

        $product1 = Product::factory()->create([
            'price' => 1000.00,
            'stock_quantity' => 50,
        ]);

        $product2 = Product::factory()->create([
            'price' => 2500.50,
            'stock_quantity' => 30,
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 3],
                ['product_id' => $product2->id, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer_id',
                    'customer',
                    'status',
                    'total_amount',
                    'items',
                    'created_at',
                ],
            ]);

        // total = 1000 * 3 + 2500.50 * 2 = 3000 + 5001 = 8001.00
        $this->assertEquals('8001.00', $response->json('data.total_amount'));
        $this->assertEquals('new', $response->json('data.status'));
        $this->assertCount(2, $response->json('data.items'));

        // Stock decreased
        $this->assertEquals(47, $product1->fresh()->stock_quantity);
        $this->assertEquals(28, $product2->fresh()->stock_quantity);

        // Order exists in DB
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => Order::STATUS_NEW,
            'total_amount' => 8001.00,
        ]);
    }

    public function test_order_creation_fails_when_insufficient_stock(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 500.00,
            'stock_quantity' => 2,
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(422);

        // Stock not changed
        $this->assertEquals(2, $product->fresh()->stock_quantity);

        // No order created
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_status_transition(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_NEW,
        ]);

        // new → confirmed
        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'confirmed',
        ]);

        $response->assertOk();
        $this->assertEquals('confirmed', $response->json('data.status'));
        $this->assertNotNull($response->json('data.confirmed_at'));
    }

    public function test_invalid_status_transition_fails(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_SHIPPED,
        ]);

        // shipped → cancelled — not allowed
        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(422);
    }
}
