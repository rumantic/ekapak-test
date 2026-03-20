<?php

namespace App\Services;

use App\DTO\CreateOrderDTO;
use App\Jobs\ExportOrderJob;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            $order = Order::create([
                'customer_id' => $dto->customerId,
                'status' => Order::STATUS_NEW,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($dto->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    throw new \DomainException(
                        "Недостаточно товара «{$product->name}» на складе. Доступно: {$product->stock_quantity}, запрошено: {$item['quantity']}."
                    );
                }

                $unitPrice = $product->price;
                $itemTotal = $unitPrice * $item['quantity'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                ]);

                $product->decrement('stock_quantity', $item['quantity']);
                $totalAmount += $itemTotal;
            }

            $order->update(['total_amount' => $totalAmount]);
            $order->load('items.product', 'customer');

            return $order;
        });
    }

    public function updateStatus(Order $order, string $newStatus): Order
    {
        if (!$order->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Невозможно сменить статус заказа с «{$order->status}» на «{$newStatus}»."
            );
        }

        $order->status = $newStatus;

        if ($newStatus === Order::STATUS_CONFIRMED) {
            $order->confirmed_at = now();
        }

        if ($newStatus === Order::STATUS_SHIPPED) {
            $order->shipped_at = now();
        }

        $order->save();

        if ($newStatus === Order::STATUS_CONFIRMED) {
            ExportOrderJob::dispatch($order);
        }

        return $order->load('items.product', 'customer');
    }
}
