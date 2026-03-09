<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Receipt;
use App\Http\Resources\ReceiptResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;  
use App\Http\Requests\StoreReceiptRequest;    
use App\Models\Product;
use App\Http\Requests\VoidReceiptRequest; 
use Carbon\Carbon;

class ReceiptController extends Controller
{
     public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $paymentMethod = $request->input('payment_method');
        $isVoided = $request->input('is_voided');

        $receipts = Receipt::with('items', 'order')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_no', 'like', "%{$search}%")
                      ->orWhere('invoice_no', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('payment_reference', 'like', "%{$search}%");
                });
            })
            ->when($fromDate, function ($query, $fromDate) {
                $query->whereDate('issue_date', '>=', $fromDate);
            })
            ->when($toDate, function ($query, $toDate) {
                $query->whereDate('issue_date', '<=', $toDate);
            })
            ->when($paymentMethod, function ($query, $paymentMethod) {
                $query->where('payment_method', $paymentMethod);
            })
            ->when(isset($isVoided), function ($query) use ($isVoided) {
                $query->where('is_voided', filter_var($isVoided, FILTER_VALIDATE_BOOLEAN));
            })
            ->latest()
            ->paginate($perPage);

        return ReceiptResource::collection($receipts)->additional([
            'status' => 'success',
            'message' => 'Receipts retrieved successfully.'
        ]);
    }

    public function show(Receipt $receipt)
    {
        return (new ReceiptResource($receipt->load(['items', 'order'])))
            ->additional([
                'status' => 'success'
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function store(StoreReceiptRequest $request, Order $order)
    {
        $validated = $request->validated();

        if ($order->status !== 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => "Receipts can only be generated for completed orders. Current status: {$order->status}"
            ], 400);
        }

        if ($order->receipt()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => "Receipt already exists for this order. Receipt #: {$order->receipt->receipt_no}"
            ], 400);
        }

        try {
            return DB::transaction(function () use ($validated, $order) {
                $order->load('items.product');
        
                $subtotal = $order->total_amount;
                $taxRate = config('app.tax_rate', 10.00);
                $taxAmount = round($subtotal * ($taxRate / 100), 2);
 
                $discountAmount = $validated['discount_amount'] ?? 0;
                $grandTotal = max(0, round(($subtotal + $taxAmount) - $discountAmount, 2));
                
                $receiptNo = 'RCP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
                
                $receipt = Receipt::create([
                    'receipt_no'        => $receiptNo,
                    'order_id'          => $order->id,
                    'invoice_no'        => $order->invoice_no,
                    'customer_name'     => $order->customer_name,
                    'customer_email'    => $validated['customer_email'] ?? null, 
                    'customer_phone'    => $validated['customer_phone'] ?? null, 
                    'issue_date'        => now(),
                    'subtotal'          => $subtotal,
                    'tax_rate'          => $taxRate,
                    'tax_amount'        => $taxAmount,
                    'discount_amount'   => $discountAmount,
                    'grand_total'       => $grandTotal,
                    'payment_method'    => $validated['payment_method'],
                    'payment_status'    => $validated['payment_status'] ?? 'paid', 
                    'payment_reference' => $validated['payment_reference'] ?? null,
                    'issued_by'         => 'system', 
                ]);

                foreach ($order->items as $item) {
                    $receipt->items()->create([
                        'product_id'   => $item->product_id,
                        'product_name' => $item->product->name ?? 'Unknown Product',
                        'quantity'     => $item->quantity,
                        'unit_price'   => $item->unit_price,
                        'subtotal'     => $item->subtotal,
                    ]);
                }

                return (new ReceiptResource($receipt->load(['items', 'order'])))
                    ->additional([
                        'status'  => 'success',
                        'message' => "Receipt generated successfully."
                    ])
                    ->response()
                    ->setStatusCode(201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to generate receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    public function void(VoidReceiptRequest $request, Receipt $receipt)
    {
        if ($receipt->is_voided) {
            return response()->json([
                'status' => 'error',
                'message' => 'Receipt is already voided.'
            ], 400);
        }

        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($receipt, $validated) {
                if ($validated['restore_stock'] ?? false) {
                    $receipt->load('items');
                }
                $receipt->update([
                    'is_voided' => true,
                    'voided_at' => now(),
                    'voided_by' => 'system',
                    'void_reason' => $validated['reason']
                ]);

                if ($validated['restore_stock'] ?? false) {
                    foreach ($receipt->items as $item) {
                        if ($item->product_id) {
                            Product::where('id', $item->product_id)
                                ->increment('stock', $item->quantity);
                        }
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Receipt voided successfully.',
                    'data' => [
                        'receipt' => new ReceiptResource($receipt->load(['items', 'order'])),
                        'stock_restored' => $validated['restore_stock'] ?? false
                    ]
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to void receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    public function summary(Request $request)
    {
        $fromDate = $request->input('from_date', now()->startOfDay());
        $toDate = $request->input('to_date', now()->endOfDay());

        $stats = Receipt::whereBetween('issue_date', [$fromDate, $toDate])
            ->where('is_voided', false)
            ->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(subtotal), 0) as total_subtotal,
                COALESCE(SUM(tax_amount), 0) as total_tax,
                COALESCE(SUM(discount_amount), 0) as total_discounts,
                COALESCE(SUM(grand_total), 0) as net_revenue
            ')
            ->first();

        $paymentMethods = Receipt::whereBetween('issue_date', [$fromDate, $toDate])
            ->where('is_voided', false)
            ->groupBy('payment_method')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(grand_total) as total'))
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => $stats,
                'by_payment_method' => $paymentMethods,
                'date_range' => [
                    'from' => $fromDate,
                    'to' => $toDate
                ]
            ]
        ]);
    }
}





    