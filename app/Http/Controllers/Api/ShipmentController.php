<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Http\Resources\ShipmentResource; 
use App\Http\Requests\StoreShipmentRequest; 
use App\Http\Requests\UpdateShipmentRequest; 
use App\Http\Requests\DeliverShipmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('search');  
        $perPage = $request->input('per_page', 15);

        $shipments = Shipment::with('order')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('carrier', 'like', "%{$search}%")
                    ->orWhereHas('order', function($orderQuery) use ($search) {
                        $orderQuery->where('invoice_no', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate($perPage);

        return ShipmentResource::collection($shipments)->additional([
            'status' => 'success',
            'message' => 'Shipments retrieved successfully.'
        ]);
    }

    public function show(Shipment $shipment)
    {
        return (new ShipmentResource($shipment->load('order')))
            ->additional(['status' => 'success'])
            ->response()
            ->setStatusCode(200);
    }

   public function store(StoreShipmentRequest $request, Order $order)
    {
        if ($order->status !== 'paid' && $order->status !== 'completed') {
            return response()->json([
                'status' => 'error', 
                'message' => "Order #{$order->id} must be paid to ship. Current status: {$order->status}"
            ], 400);
        }

        $existingShipment = $order->shipment()
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingShipment) {
            return response()->json([
                'status' => 'error',
                'message' => "An active shipment already exists. Tracking: {$existingShipment->tracking_number}"
            ], 400);
        }

        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($order, $validated) {

                $order = Order::lockForUpdate()->find($order->id);

                $shipment = $order->shipment()->create([
                    'tracking_number' => $validated['tracking_number'],
                    'carrier'         => $validated['carrier'],
                    'ship_date'       => $validated['ship_date'],
                    'status'          => 'in_transit'
                ]);

                $order->update(['status' => 'shipped']);

                return (new ShipmentResource($shipment->load('order')))
                    ->additional([
                        'status' => 'success',
                        'message' => 'Shipment created and order status updated to shipped.'
                    ])
                    ->response()
                    ->setStatusCode(201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process shipment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deliver(DeliverShipmentRequest $request, Shipment $shipment)
    {
        if ($shipment->status === 'delivered') {
            return response()->json([
                'status' => 'error',
                'message' => 'This shipment has already been delivered.'
            ], 400);
        }

        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($shipment, $validated) {
                $shipment->update([
                    'status'  => 'delivered',
                    'delivered_at'      => $validated['delivered_at'] ?? now(),
                    'proof_of_delivery' => $validated['proof_of_delivery'] ?? $shipment->proof_of_delivery,
                    'delivery_notes'    => $validated['delivery_notes'] ?? $shipment->delivery_notes,
                ]);

                $shipment->order->update(['status' => 'completed']);

                return (new ShipmentResource($shipment->load('order')))
                    ->additional([
                        'status' => 'success',
                        'message' => 'Shipment delivered and order finalized.'
                    ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delivery update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment)
    {
        if ($shipment->status === 'delivered') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update details of a delivered shipment.'
            ], 400);
        }

        $validated = $request->validated();

        try {
            $shipment->update($validated);

            return (new ShipmentResource($shipment->load('order')))
                ->additional([
                    'status' => 'success',
                    'message' => 'Shipment updated successfully.'
                ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update shipment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function summary()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'in_transit' => Shipment::where('status', 'in_transit')->count(),
                'delivered_today' => Shipment::where('status', 'delivered')
                                        ->whereDate('delivered_at', now())
                                        ->count(),
                'cancelled_total' => Shipment::where('status', 'cancelled')->count(),
                'ready_to_ship'   => Order::where('status', 'paid')
                                        ->whereDoesntHave('shipment')
                                        ->count(),
            ]
        ]);
    }
}
