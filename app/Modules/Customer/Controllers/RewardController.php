<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rewards = Reward::with('product')->get();
        return response()->json($rewards, Response::HTTP_OK);
    }

    /**
     * Get active rewards only.
     */
    public function getActiveRewards()
    {
        $rewards = Reward::where('is_active', true)
            ->with('product')
            ->get();
        return response()->json($rewards, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate common fields
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage_discount,free_item',
            'pointsNeeded' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Additional validation based on reward type
        if ($request->type === 'percentage_discount') {
            $request->validate([
                'value' => 'required|numeric|min:0.01|max:100',
                'product_id' => 'nullable',
            ]);
        } else if ($request->type === 'free_item') {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'value' => 'nullable',
            ]);

            // Check if product exists for free_item type
            $product = Product::find($request->product_id);
            if (!$product) {
                return response()->json([
                    'message' => 'Selected product does not exist.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $reward = Reward::create($request->all());

        return response()->json([
            'message' => 'Reward created successfully.',
            'reward' => $reward
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reward $reward)
    {
        $reward->load('product');
        return response()->json($reward, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reward $reward)
    {
        // Base validation rules
        $rules = [
            'name' => 'sometimes|string|max:50',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:percentage_discount,free_item',
            'pointsNeeded' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ];

        // Add type-specific validation rules
        if (isset($request->type)) {
            $type = $request->type;
        } else {
            $type = $reward->type;
        }

        if ($type === 'percentage_discount') {
            $rules['value'] = 'sometimes|numeric|min:0.01|max:100';
            $rules['product_id'] = 'nullable';
        } else if ($type === 'free_item') {
            $rules['product_id'] = 'sometimes|exists:products,id';
            $rules['value'] = 'nullable';
        }

        $validated = $request->validate($rules);

        // Check if product exists for free_item type
        if ($type === 'free_item' && isset($request->product_id)) {
            $product = Product::find($request->product_id);
            if (!$product) {
                return response()->json([
                    'message' => 'Selected product does not exist.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $reward->update($validated);

        return response()->json([
            'message' => 'Reward updated successfully.',
            'reward' => $reward
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reward $reward)
    {
        try {
            // Begin a transaction
            DB::beginTransaction();
            
            // Delete any related rewards history
            \App\Models\RewardsHistory::where('reward_id', $reward->id)->delete();
            
            // Clear any references in transactions
            \App\Models\Transaction::where('reward_id', $reward->id)->update(['reward_id' => null]);
            
            // Now delete the reward
            $reward->delete();
            
            // Commit the transaction
            DB::commit();
            
            return response()->json(['message' => 'Reward deleted successfully.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Roll back the transaction if there was an error
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to delete reward.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 