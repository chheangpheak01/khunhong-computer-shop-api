<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\ShipmentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);    

Route::middleware('auth:sanctum')->group(function () {
       // Auth Management
    Route::get('/user-details', [AuthController::class, 'userDetails']);
    Route::post('/logout', [AuthController::class, 'logout']);

       // Trash & Recovery (Categories)
    Route::get('/categories/trashed', [CategoryController::class, 'trashed']);
    Route::post('/categories/{id}/restore', [CategoryController::class, 'restore']); 
    Route::delete('/categories/{id}/force', [CategoryController::class, 'forceDelete']);
        // General Management: Store, Update, Destroy for Category
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);

        // Trash & Recovery (Products)
    Route::get('/products/trashed', [ProductController::class, 'trashed']);      
    Route::post('/products/{id}/restore', [ProductController::class, 'restore']);
    Route::delete('/products/{id}/force', [ProductController::class, 'forceDelete']); 
        // General Management: Store, Update, Destroy for Product
    Route::apiResource('products', ProductController::class)->except(['index', 'show']); 

        // Professional way to handle cancellations
    Route::get('orders/statuses', [OrderController::class, 'statuses']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
        // General Management: Store, Update for Order
    Route::apiResource('orders', OrderController::class)->except(['destroy']); 
    
        // Receipt
    Route::get('/receipts/summary', [ReceiptController::class, 'summary']);
        // Receipt Generation tied to an Order
    Route::post('/orders/{order}/receipt', [ReceiptController::class, 'store']);
    Route::post('/receipts/{receipt}/void', [ReceiptController::class, 'void']);
        // General Management: Index, Show are protected
    Route::apiResource('receipts', ReceiptController::class)->only(['index', 'show']);

        // Shipment Generation tied to an Order 
    Route::post('/orders/{order}/shipment', [ShipmentController::class, 'store']);
    Route::patch('shipments/{shipment}/deliver', [ShipmentController::class, 'deliver']);
    Route::get('/shipments/summary', [ShipmentController::class, 'summary']);
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);
    Route::put('/shipments/{shipment}', [ShipmentController::class, 'update']);
    Route::post('/shipments/{shipment}/cancel', [ShipmentController::class, 'cancel']);
    Route::apiResource('shipments', ShipmentController::class)->only(['index']);
});

Route::get('/categories/{category}', [CategoryController::class, 'show']); 
Route::get('/products/{product}', [ProductController::class, 'show']);  