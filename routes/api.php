<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ShipmentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);    

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user-details', [AuthController::class, 'userDetails']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories/trashed', [CategoryController::class, 'trashed']);
    Route::post('/categories/{id}/restore', [CategoryController::class, 'restore']); 
    Route::delete('/categories/{id}/force', [CategoryController::class, 'forceDelete']);
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);

    Route::get('/products/trashed', [ProductController::class, 'trashed']);      
    Route::post('/products/{id}/restore', [ProductController::class, 'restore']);
    Route::delete('/products/{id}/force', [ProductController::class, 'forceDelete']); 
    Route::apiResource('products', ProductController::class)->except(['index', 'show']); 

      
    Route::get('orders/statuses', [OrderController::class, 'statuses']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::apiResource('orders', OrderController::class)->except(['destroy']); 
    
    Route::get('/invoices/summary', [InvoiceController::class, 'summary']);
    Route::post('/orders/{order}/invoice', [InvoiceController::class, 'store']);
    Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void']);
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::put('/orders/{order}/payment-status', [InvoiceController::class, 'updatePaymentStatus']);

    Route::post('/orders/{order}/shipment', [ShipmentController::class, 'store']);
    Route::patch('shipments/{shipment}/deliver', [ShipmentController::class, 'deliver']);
    Route::get('/shipments/summary', [ShipmentController::class, 'summary']);
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);
    Route::put('/shipments/{shipment}', [ShipmentController::class, 'update']);
    Route::apiResource('shipments', ShipmentController::class)->only(['index']);
});

Route::get('/categories/{category}', [CategoryController::class, 'show']); 
Route::get('/products/{product}', [ProductController::class, 'show']);  