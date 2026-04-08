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
                'paid' => 'Paid',  
                'shipped' => 'Shipped',
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
        $date = $request->input('date');

        $orders = Order::with(['items.product', 'payments']) 
            ->when($search, function ($query, $search) {
                $query->where('invoice_no', 'like', "%{$search}%");
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($date, function ($query, $date) {
                $query->whereDate('created_at', $date);
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
        return (new OrderResource($order->load(['items.product', 'payments', 'shipment'])))->additional(
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
                    'total_amount' => 0, 
                    'status' => 'pending',
                    'customer_name'    => $validated['customer_name'],
                    'customer_phone'   => $validated['customer_phone'],
                    'shipping_address' => $validated['shipping_address'],
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
                        'is_restocked' => false,
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
        if (in_array($order->status, ['paid', 'shipped', 'completed', 'cancelled'])) {
            return response()->json([
                'status' => 'error',
                'message' => "Order cannot be updated because it is already {$order->status}."
            ], 400);
        }

        $validated = $request->validated();

        $order->update($validated);

        return (new OrderResource($order->load(['items.product', 'payments'])))->additional([
            'status' => 'success',
            'message' => 'Order updated successfully.'
        ]);
    }

    public function cancel(Order $order)
    {
        if (in_array($order->status, ['shipped', 'completed', 'cancelled'])) {
            return response()->json([
                'status' => 'error',
                'message' => "Order cannot be cancelled because it is already {$order->status}."
            ], 400);
        }
        try {
            return DB::transaction(function () use ($order) {
                $order->load(['items', 'payments']);
                foreach ($order->items as $item) {
                    if (!$item->is_restocked) {
                        $product = Product::lockForUpdate()->find($item->product_id);
                        if ($product) {
                            $product->increment('stock', $item->quantity);
                        }
                        $item->update(['is_restocked' => true]);
                    }
                }
                $order->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
                $order->payments()->where('is_voided', false)->update([
                    'status' => 'voided',
                    'is_voided' => true,
                    'voided_at' => now(),
                    'void_reason' => 'Order cancelled by customer/admin'
                ]);
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
