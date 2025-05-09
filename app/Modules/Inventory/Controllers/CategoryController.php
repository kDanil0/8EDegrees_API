<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:categories',
        ]);

        $category = Category::create($validated);

        return response()->json($category, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json($category, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:categories,name,' . $category->id,
        ]);

        $category->update($validated);

        return response()->json($category, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // Check if this category is being used by any products
        $productsCount = $category->products()->count();
        
        if ($productsCount > 0) {
            return response()->json([
                'error' => 'Cannot delete category. It is being used by ' . $productsCount . ' products.'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $category->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Get all products for a specific category
     */
    public function getProducts(Category $category)
    {
        $products = $category->products()->get();
        return response()->json($products, Response::HTTP_OK);
    }
} 