<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    /**
     * Get all rewards.
     */
    public function index()
    {
        $rewards = Reward::with('product')->get();
        return response()->json($rewards);
    }

    /**
     * Get active rewards.
     */
    public function getActiveRewards()
    {
        $rewards = Reward::where('is_active', true)
            ->with('product')
            ->get();
        return response()->json($rewards);
    }

    /**
     * Store a newly created reward.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage_discount,free_item',
            'pointsNeeded' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Add conditional validation based on reward type
        if ($request->type === 'percentage_discount') {
            $validator->addRules([
                'value' => 'required|numeric|min:0.01|max:100',
                'product_id' => 'nullable',
            ]);
        } else if ($request->type === 'free_item') {
            $validator->addRules([
                'product_id' => 'required|exists:products,id',
                'value' => 'nullable',
            ]);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if product exists for free_item type
        if ($request->type === 'free_item' && $request->product_id) {
            $product = Product::find($request->product_id);
            if (!$product) {
                return response()->json(['error' => 'Selected product does not exist.'], 422);
            }
        }

        $reward = Reward::create($request->all());
        
        return response()->json([
            'message' => 'Reward created successfully.',
            'reward' => $reward
        ], 201);
    }

    /**
     * Display the specified reward.
     */
    public function show($id)
    {
        $reward = Reward::with('product')->find($id);
        
        if (!$reward) {
            return response()->json(['error' => 'Reward not found.'], 404);
        }
        
        return response()->json($reward);
    }

    /**
     * Update the specified reward.
     */
    public function update(Request $request, $id)
    {
        $reward = Reward::find($id);
        
        if (!$reward) {
            return response()->json(['error' => 'Reward not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:50',
            'description' => 'nullable|string',
            'type' => 'in:percentage_discount,free_item',
            'pointsNeeded' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Add conditional validation based on reward type
        if ($request->type === 'percentage_discount' || 
            (!$request->has('type') && $reward->type === 'percentage_discount')) {
            $validator->addRules([
                'value' => 'numeric|min:0.01|max:100',
                'product_id' => 'nullable',
            ]);
        } else if ($request->type === 'free_item' || 
                  (!$request->has('type') && $reward->type === 'free_item')) {
            $validator->addRules([
                'product_id' => 'exists:products,id',
                'value' => 'nullable',
            ]);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reward->update($request->all());
        
        return response()->json([
            'message' => 'Reward updated successfully.',
            'reward' => $reward
        ]);
    }

    /**
     * Remove the specified reward.
     */
    public function destroy($id)
    {
        $reward = Reward::find($id);
        
        if (!$reward) {
            return response()->json(['error' => 'Reward not found.'], 404);
        }
        
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
            
            return response()->json(['message' => 'Reward deleted successfully.']);
        } catch (\Exception $e) {
            // Roll back the transaction if there was an error
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to delete reward.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 