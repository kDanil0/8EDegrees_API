<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check if we should include deleted products
        $includeDeleted = $request->query('include_deleted', false);
        
        if ($includeDeleted) {
            // Include soft deleted products
            $products = Product::with('category')->withTrashed()->get();
        } else {
            // Default behavior - only active products
            $products = Product::with('category')->get();
        }
        
        return response()->json($products, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'quantity' => 'required|integer',
            'category_id' => 'required|exists:categories,id',
            'reorderLevel' => 'required|integer',
            'price' => 'required|numeric',
            'expiryDate' => 'nullable|date',
        ]);

        // Generate SKU
        $category = Category::findOrFail($request->category_id);
        $sku = $this->generateSku($request->name, $category->name);
        
        $validated['sku'] = $sku;
        
        // Automatically set status based on quantity
        $validated['status'] = $this->determineStatus($validated['quantity'], $validated['reorderLevel']);
        
        $product = Product::create($validated);

        return response()->json($product->load('category'), Response::HTTP_CREATED);
    }

    /**
     * Generate a unique SKU for the product based on name and category
     */
    private function generateSku($productName, $categoryName)
    {
        // Generate category code (first 3 chars uppercase)
        $categoryCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $categoryName), 0, 3));
        
        // Check if a product with the same name and category exists
        $existingProduct = Product::where('name', $productName)
            ->whereHas('category', function($query) use ($categoryName) {
                $query->where('name', $categoryName);
            })
            ->first();
        
        if ($existingProduct) {
            // Return the existing SKU if found
            return $existingProduct->sku;
        }
        
        // Find the highest sequential number for this category code
        $latestSku = Product::where('sku', 'LIKE', $categoryCode . '-%')
            ->orderBy('sku', 'desc')
            ->first();
        
        if ($latestSku) {
            // Extract the number part
            $parts = explode('-', $latestSku->sku);
            $lastNumber = intval($parts[1] ?? 0);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        // Format as CCC-NNN
        return $categoryCode . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json($product->load('category'), Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'description' => 'nullable|string|max:255',
            'quantity' => 'sometimes|integer',
            'category_id' => 'sometimes|exists:categories,id',
            'reorderLevel' => 'sometimes|integer',
            'price' => 'sometimes|numeric',
            'expiryDate' => 'nullable|date',
        ]);

        // If quantity or reorderLevel is changed, update status automatically
        if (isset($validated['quantity']) || isset($validated['reorderLevel'])) {
            $quantity = $validated['quantity'] ?? $product->quantity;
            $reorderLevel = $validated['reorderLevel'] ?? $product->reorderLevel;
            $validated['status'] = $this->determineStatus($quantity, $reorderLevel);
        }

        $product->update($validated);

        return response()->json($product->load('category'), Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Instead of hard delete, we'll soft delete (mark as deleted)
        try {
            $product->delete(); // This now uses the SoftDeletes trait
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            \Log::error('Product delete error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to delete product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get products with low stock.
     */
    public function lowStock()
    {
        try {
            $products = Product::with('category')
                ->whereRaw('quantity <= reorderLevel')
                ->get();
            
            return response()->json($products, Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Low stock error: ' . $e->getMessage());
            
            // Return empty array instead of a 404 error
            return response()->json([], Response::HTTP_OK);
        }
    }

    /**
     * Get products nearing expiration.
     */
    public function expirationReport()
    {
        try {
            // Calculate the date 30 days from now
            $thirtyDaysFromNow = now()->addDays(30)->format('Y-m-d');
            
            $products = Product::with('category')
                ->whereNotNull('expiryDate')
                ->whereDate('expiryDate', '<=', $thirtyDaysFromNow)
                ->orderBy('expiryDate')
                ->get();
            
            return response()->json($products, Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Expiration report error: ' . $e->getMessage());
            
            // Return empty array instead of a 404 error
            return response()->json([], Response::HTTP_OK);
        }
    }

    /**
     * Determine product status based on quantity and reorder level
     */
    private function determineStatus($quantity, $reorderLevel)
    {
        if ($quantity <= 0) {
            return 'Out of Stock';
        } elseif ($quantity <= $reorderLevel) {
            return 'Low Stock';
        } else {
            return 'In Stock';
        }
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore($id)
    {
        try {
            $product = Product::withTrashed()->findOrFail($id);
            $product->restore();
            return response()->json($product->load('category'), Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Product restore error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to restore product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get all soft-deleted products.
     */
    public function deleted()
    {
        try {
            $products = Product::with('category')->onlyTrashed()->get();
            return response()->json($products, Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Get deleted products error: ' . $e->getMessage());
            return response()->json([], Response::HTTP_OK);
        }
    }
} 