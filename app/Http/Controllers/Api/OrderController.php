<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function statuses()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'pending' => 'Pending',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled'
            ]
        ]);
    }
    
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search'); 
        $status = $request->input('status'); 

        $orders = Order::with('items.product')
            ->when($search, function ($query, $search)
             {
                $query->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
             })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($perPage);

        return OrderResource::collection($orders)->additional([
            'status' => 'success',
            'message' => 'Orders retrieved successfully.'
        ]);
    }

    public function show(Order $order)
    {
        return (new OrderResource($order->load('items.product')))->additional(
            [
                'status' => 'success'
        ])
        ->response()
        ->setStatusCode(200);
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        try {
            $mergedItems = collect($validated['items'])
                ->groupBy('product_id')
                ->map(function ($group) {
                    return [
                        'product_id' => $group->first()['product_id'],
                        'quantity' => $group->sum('quantity'),
                    ];
                })->values();

            return DB::transaction(function () use ($mergedItems, $validated) {
                
                $order = Order::create([
                    'invoice_no' => 'KH-' . now()->format('YmdHis') . strtoupper(Str::random(4)),
                    'customer_name' => $validated['customer_name'] ?? null,
                    'total_amount' => 0, 
                    'status' => 'pending',
                ]);

                $totalAmount = 0;

                foreach ($mergedItems as $item) {
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

                $order->update(['total_amount' => round($totalAmount, 2)]);

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
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'status' => 'error',
                'message' => "Order can not be updated because it is already {$order->status}."
            ], 400);
        }

        $validated = $request->validated();

        if (isset($validated['status']) && $validated['status'] !== $order->status) {
            return response()->json([
                'status' => 'error',
                'message' => "Please use the specialized /cancel or /receipt endpoints to change order status."
            ], 422);
        }

        $order->update($validated);

        return (new OrderResource($order->load('items.product')))->additional([
            'status' => 'success',
            'message' => 'Order status updated successfully.'
        ]);
    }

    public function cancel(Order $order)
    {
        if (in_array($order->status, ['cancelled', 'completed'])) {
            return response()->json([
                'status' => 'error',
                'message' => "Order can not be cancelled because it is already {$order->status}."
            ], 400);
        }
        try {
            return DB::transaction(function () use ($order) {
                $order->load('items');
                foreach ($order->items as $item) {
                    $product = Product::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->increment('stock', $item->quantity);
                    }
                }
                $order->update(['status' => 'cancelled']);

                return (new OrderResource($order->load('items.product')))->additional([
                    'status' => 'success',
                    'message' => 'Order cancelled and stock restored successfully.'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'System error during cancellation: ' . $e->getMessage()
            ], 500);
        }
    }
   
}
