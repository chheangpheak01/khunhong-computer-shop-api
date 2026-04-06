<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Invoice;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreInvoiceRequest;    
use App\Models\Product;
use App\Http\Requests\VoidInvoiceRequest; 
use Carbon\Carbon;
use App\Http\Requests\UpdatePaymentStatusRequest;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $paymentMethod = $request->input('payment_method');
        $isVoided = $request->input('is_voided');

        $invoices = Invoice::with(['order.payment', 'details']) 
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoices.invoice_no', 'like', "%{$search}%")
                    ->orWhere('invoices.order_id', 'like', "%{$search}%")
                    ->orWhereHas('order.payment', function($pay) use ($search) {
                        $pay->where('reference_no', 'like', "%{$search}%");
                    });
                });
            })
            ->when($fromDate, function ($query, $fromDate) {
                $query->whereDate('invoices.created_at', '>=', $fromDate);
            })
            ->when($toDate, function ($query, $toDate) {
                $query->whereDate('invoices.created_at', '<=', $toDate);
            })
            ->when($paymentMethod, function ($query, $paymentMethod) {
                $query->whereHas('order.payment', fn($q) => $q->where('method', $paymentMethod));
            })
            ->when(isset($isVoided), function ($query) use ($isVoided) {
                $bool = filter_var($isVoided, FILTER_VALIDATE_BOOLEAN);
                $query->whereHas('order.payment', fn($q) => $q->where('is_voided', $bool));
            })
            ->latest('invoices.created_at')
            ->paginate($perPage);

        return InvoiceResource::collection($invoices)->additional([
            'status' => 'success',
            'message' => 'Invoices retrieved successfully.'
        ]);
    }

    public function show(Invoice $invoice)
    {
        return (new InvoiceResource($invoice->load(['details', 'order.payment'])))
            ->additional([
                'status' => 'success'
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function store(StoreInvoiceRequest $request, Order $order)
    {
        $validated = $request->validated();

        if ($order->status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot generate an invoice for a cancelled order."
            ], 400);
        }

        $activeInvoice = $order->invoice()
            ->whereHas('order.payment', fn($q) => $q->where('is_voided', false))
            ->first();

        if ($activeInvoice) {
            return response()->json([
                'status' => 'error',
                'message' => "An active invoice already exists for this order (#{$activeInvoice->invoice_no})."
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
                
                $invoice = Invoice::create([
                    'order_id'          => $order->id,
                    'invoice_no' => $order->invoice_no,
                    'customer_name'     => $order->customer_name,
                    'customer_email' => $order->customer_email, 
                    'customer_phone' => $order->customer_phone,
                    'shipping_address'  => $order->shipping_address,
                    'issue_date'        => now(),
                    'subtotal'          => $subtotal,
                    'tax_rate'          => $taxRate,
                    'tax_amount'        => $taxAmount,
                    'discount_amount'   => $discountAmount,
                    'grand_total'       => $grandTotal,
                ]);

                foreach ($order->items as $item) {
                    $invoice->details()->create([
                        'product_id'   => $item->product_id,
                        'product_name' => $item->product->name ?? 'Unknown Product',
                        'quantity'     => $item->quantity,
                        'unit_price'   => $item->unit_price,
                        'subtotal'     => $item->subtotal,
                    ]);
                }

                $paymentStatus = in_array($validated['payment_method'], ['credit_card', 'bank_transfer', 'qr_pay']) ? 'paid' : 'pending';

                $order->payment()->create([
                    'method'         => $validated['payment_method'],
                    'amount'         => $grandTotal,
                    'status'         => $paymentStatus,
                    'reference_no'   => $validated['payment_reference'] ?? null,
                    'payment_date'   => now(),
                    'is_voided'      => false,
                ]);

                if ($paymentStatus === 'paid') {
                    $order->update(['status' => 'paid']);
                }

                return (new InvoiceResource($invoice->load(['details', 'order.payment'])))
                        ->additional([
                            'status'  => 'success',
                            'message' => "Invoice generated successfully."
                        ])
                        ->response()
                        ->setStatusCode(201);
                });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to generate invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function void(VoidInvoiceRequest $request, Invoice $invoice)
    {
        $payment = $invoice->order->payment;

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'No payment record found for this invoice.'
            ], 404);
        }

        if ($payment->is_voided) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment is already voided.'
            ], 400);
        }

        if ($invoice->order->status === 'shipped') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot void payment for an order that has already been shipped.'
            ], 400);
        }

        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($payment, $validated, $invoice) {
                $payment->update([
                    'is_voided' => true,
                    'voided_at' => now(),
                    'voided_by' => 'system',
                    'void_reason' => $validated['reason'] ?? 'Customer request'  
                ]);

                if ($validated['restore_stock'] ?? false) {
                    foreach ($invoice->order->items as $item) {
                        if ($item->product_id) {
                            Product::where('id', $item->product_id)
                                ->increment('stock', $item->quantity);
                        }
                    }
                    $invoice->order->update(['status' => 'cancelled']);
                }else {
                    $invoice->order->update(['status' => 'pending']);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment voided successfully.',
                    'data' => [
                        'invoice' => new InvoiceResource($invoice->load(['details', 'order.payment'])),
                        'stock_restored' => $validated['restore_stock'] ?? false
                    ]
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to void payment: ' . $e->getMessage()
            ], 500);
        }
    }
    public function summary(Request $request)
    {
        $fromDate = $request->filled('from_date') ? Carbon::parse($request->from_date)->startOfDay() : now()->startOfDay();
        $toDate = $request->filled('to_date') ? Carbon::parse($request->to_date)->endOfDay() : now()->endOfDay();

        $stats = Invoice::whereBetween('invoices.created_at', [$fromDate, $toDate]) 
            ->whereHas('order.payment', fn($q) => $q->where('is_voided', false))
            ->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(tax_amount), 0) as total_tax,
                COALESCE(SUM(discount_amount), 0) as total_discounts,
                COALESCE(SUM(grand_total), 0) as net_revenue
            ')->first();

        $paymentMethods = Invoice::whereBetween('invoices.created_at', [$fromDate, $toDate])
            ->join('orders', 'invoices.order_id', '=', 'orders.id')
            ->join('payments', 'orders.id', '=', 'payments.order_id')
            ->where('payments.is_voided', false)
            ->groupBy('payments.method')
            ->select(
                'payments.method as payment_method', 
                DB::raw('COUNT(*) as count'), 
                DB::raw('SUM(payments.amount) as total')
            )->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => $stats,
                'by_payment_method' => $paymentMethods,
                'date_range' => [
                    'from' => $fromDate->toDateTimeString(),
                    'to' => $toDate->toDateTimeString()
                ]
            ]
        ]);
    }

    public function updatePaymentStatus(UpdatePaymentStatusRequest $request, Order $order)
    {
        $order->payment->update(['status' => $request->status]);
        
        if ($request->status === 'paid') {
            $order->update(['status' => 'paid']);
        }
        return response()->json([
            'message' => 'Payment status updated successfully',
            'data' => [
                'order_id' => $order->id,
                'payment_status' => $request->status,
                'order_status' => $order->status
            ]
        ]);
    }
}





    