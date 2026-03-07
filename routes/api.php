<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;

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

        // General Management: Store, Update, Destroy for Order
    Route::apiResource('orders', OrderController::class)->except(['destroy']); 
});

Route::get('/categories/{category}', [CategoryController::class, 'show']); 
Route::get('/products/{product}', [ProductController::class, 'show']);  