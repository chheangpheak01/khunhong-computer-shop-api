<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); 
        $search = $request->input('search');

        $categories = Category::where('status', true)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
        
        return CategoryResource::collection($categories)->additional([
            'status' => 'success',
            'message' => $search 
                ? "Categories matching '{$search}' retrieved successfully." 
                : 'Categories retrieved successfully.'
        ]);
    }

    public function show(Category $category)
    {
        return (new CategoryResource($category->load('products')))->additional(
            ['status' => 'success'
        ])
        ->response()
        ->setStatusCode(200);
    }

    public function trashed(Request $request)
    {
        $perPage = $request->input('per_page', 10); 
        $search = $request->input('search');

        $trashed = Category::onlyTrashed()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })->paginate($perPage);

        return CategoryResource::collection($trashed)->additional([
            'status' => 'success',
            'message' => 'Trashed categories retrieved successfully.'
        ]);
    }

    public function restore($id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();
        $category->update(['status' => true]);

        return (new CategoryResource($category))->additional(
            [
                'status' => 'success',
                'message' => 'Category restored successfully.'
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $validated['status'] = $request->input('status', true);
        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);
        
        return (new CategoryResource($category))->additional(
            [
                'status' => 'success',
                'message' => 'Category created successfully.'
        ])
        ->response()
        ->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        if ($category->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => "Category '{$category->name}' is in the trash. Please restore it before updating."
            ], 400); 
        }

        $validated = $request->validated();
        
        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return (new CategoryResource($category))->additional(
            [
                'status' => 'success',
                'message' => 'Category updated successfully.'
            ])
            ->response()
            ->setStatusCode(200);
        }

    public function destroy(Category $category)
    {
        if ($category->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => "Category '{$category->name}' is already in the trash."
            ], 409); 
        }
        $category->update(['status' => false]); 
        $category->delete();

        return (new CategoryResource($category))->additional(
            [
                'status' => 'success',
                'message' => 'Category moved to trash successfully.'
            ])
            ->response()
            ->setStatusCode(200);
        }

    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        if ($category->image) {
        Storage::disk('public')->delete($category->image);
        }
        $category->forceDelete();

        return response()->noContent();
    }
}