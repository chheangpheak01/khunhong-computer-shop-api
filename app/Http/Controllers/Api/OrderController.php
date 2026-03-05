<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        try {
            return DB::transaction(function () use ($validated) {
                
                $order = Order::create([
                    'invoice_no' => 'KH-' . now()->format('YmdHis') . strtoupper(Str::random(4)),
                    'customer_name' => $validated['customer_name'] ?? null,
                    'total_amount' => 0, 
                    'status' => 'pending',
                ]);

                $totalAmount = 0;

                foreach ($validated['items'] as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Insufficient stock for {$product->name}");
                    }

                    $subtotal = $product->price * $item['quantity'];
                    $totalAmount += $subtotal;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'subtotal' => $subtotal,
                    ]);

                    $product->decrement('stock', $item['quantity']);
                }

                $order->update(['total_amount' => $totalAmount]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order completed successfully',
                    'data' => new OrderResource($order->load('items.product'))
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $order->update($request->validated());

        return response()->json([
            'message' => 'Order status updated',
            'data' => new OrderResource($order->load('items.product'))
        ]);
    }
}
