<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $discounts = Discount::all();
        return response()->json($discounts, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $discount = Discount::create($validated);

        return response()->json($discount, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Discount $discount)
    {
        return response()->json($discount, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Discount $discount)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:50',
            'percentage' => 'sometimes|required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $discount->update($validated);

        return response()->json($discount, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Discount $discount)
    {
        $discount->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
    
    /**
     * Get all active discounts.
     */
    public function getActiveDiscounts()
    {
        $discounts = Discount::where('is_active', true)->get();
        return response()->json($discounts, Response::HTTP_OK);
    }
} 