<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    /**
     * Display a listing of active products with pagination.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); 
        $products = Product::where('status', true)->with('category')->paginate($perPage);
        
        return ProductResource::collection($products)->additional(
            [
                'status' => 'success',
                'message' => 'Products retrieved successfully.'
            ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['slug'] = Str::slug($validated['name']);
        $validated['status'] = $request->input('status', true);

        $product = Product::create($validated);
        
        return (new ProductResource($product->load('category')))->additional(
            [
                'status' => 'success',
                'message' => 'Product created successfully.'
        ])
        ->response()
        ->setStatusCode(201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        return (new ProductResource($product->load('category')))->additional(
            [
                'status' => 'success'
        ])
        ->response()
        ->setStatusCode(200);;
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        if ($product->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => "Product is in the trash. Please restore it before updating."
            ], 400); 
        }

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        return (new ProductResource($product->load('category')))->additional(
            [
                'status' => 'success',
                'message' => 'Product updated successfully.'
        ])
        ->response()
        ->setStatusCode(200);;
    }

    /**
     * Remove the specified product (Soft Delete).
     */
    public function destroy(Product $product)
    {
        if ($product->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => "Product is already in the trash."
            ], 409); 
        }

        $product->delete();

        return (new ProductResource($product))->additional([
            'status' => 'success',
            'message' => 'Product moved to trash successfully.'
        ])
        ->response()
        ->setStatusCode(200);
    }

    /**
     * Display trashed products.
     */
    public function trashed(Request $request)
    {
        $perPage = $request->input('per_page', 10); 
        $trashed = Product::onlyTrashed()->with('category')->paginate($perPage);

        return ProductResource::collection($trashed)->additional(
            [
                'status' => 'success',
                'message' => 'Trashed products retrieved successfully.'
        ]) 
        ->response()
        ->setStatusCode(200);
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return (new ProductResource($product))->additional(
            [
                'status' => 'success',
                'message' => 'Product restored successfully.'
        ])
        ->response()
        ->setStatusCode(200);
    }

    /**
     * Permanently delete a product.
     */
    public function forceDelete($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->forceDelete();

        return response()->noContent();
    }
}