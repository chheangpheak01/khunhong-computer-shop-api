<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); 
        $categories = Category::where('status', true)->paginate($perPage);
        
        return CategoryResource::collection($categories)->additional(
            [
                'status' => 'success',
                'message' => 'Categories retrieved successfully.'
        ]);
    }

    public function show(Category $category)
    {
        return (new CategoryResource($category->load('products')))->additional(
            ['status' => 'success']);
    }

    public function trashed(Request $request)
    {
        $perPage = $request->input('per_page', 10); 
        $trashed = Category::onlyTrashed()->paginate($perPage);

        return CategoryResource::collection($trashed)->additional(
            [
                'status' => 'success',
                'message' => 'Trashed categories retrieved successfully.'
        ]);
    }

    public function restore($id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        return (new CategoryResource($category))->additional(
            [
                'status' => 'success',
                'message' => 'Category restored successfully.'
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

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
        if (!$request->hasAny(['name', 'description', 'status'])) {
            return response()->json([
                'message' => 'No data provided for update.'
                ], 400);
        }

        $validated = $request->validated();

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
        $category->forceDelete();

        return response()->noContent();
    }
}