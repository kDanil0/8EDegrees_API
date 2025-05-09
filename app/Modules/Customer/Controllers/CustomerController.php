<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Reward;
use App\Models\RewardsHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::all();
        return response()->json($customers, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'contactNum' => 'required|string|max:20|unique:customers',
            'points' => 'integer',
            'eligibleForRewards' => 'boolean',
        ]);

        $customer = Customer::create($validated);

        return response()->json($customer, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        return response()->json($customer, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'contactNum' => 'sometimes|string|max:20|unique:customers,contactNum,' . $customer->id,
            'points' => 'sometimes|integer',
            'eligibleForRewards' => 'sometimes|boolean',
        ]);

        $customer->update($validated);

        return response()->json($customer, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Get customer's rewards history.
     */
    public function rewardsHistory(Customer $customer)
    {
        $rewardsHistory = $customer->rewardsHistory()->with('reward')->get();
        return response()->json($rewardsHistory, Response::HTTP_OK);
    }

    /**
     * Add points to a customer.
     */
    public function addPoints(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $customer->points += $validated['points'];
        
        // Get the minimum points needed for any reward
        $minPointsNeeded = Reward::min('pointsNeeded');
        
        // Check if customer is eligible for rewards based on points comparison
        $customer->eligibleForRewards = $customer->points >= ($minPointsNeeded ?? 0);
        
        $customer->save();

        return response()->json($customer, Response::HTTP_OK);
    }

    /**
     * Redeem a reward.
     */
    public function redeemReward(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'reward_id' => 'required|exists:rewards,id',
        ]);

        DB::beginTransaction();

        try {
            $reward = Reward::findOrFail($validated['reward_id']);

            // Check if customer has enough points
            if ($customer->points < $reward->pointsNeeded) {
                return response()->json([
                    'error' => 'Not enough points to redeem this reward.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Create rewards history entry
            RewardsHistory::create([
                'customer_id' => $customer->id,
                'reward_id' => $reward->id,
            ]);

            // Deduct points
            $customer->points -= $reward->pointsNeeded;
            
            // Get the minimum points needed for any reward
            $minPointsNeeded = Reward::min('pointsNeeded');
            
            // Check if customer is still eligible for rewards based on points comparison
            $customer->eligibleForRewards = $customer->points >= ($minPointsNeeded ?? 0);
            
            $customer->save();

            DB::commit();

            return response()->json([
                'customer' => $customer,
                'reward' => $reward,
                'message' => 'Reward redeemed successfully.'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search for a customer by phone number
     */
    public function search(Request $request)
    {
        $phone = $request->query('phone');
        
        if (!$phone) {
            return response()->json(['error' => 'Phone number is required'], Response::HTTP_BAD_REQUEST);
        }

        $customer = Customer::where('contactNum', $phone)->first();
        
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        // Update eligibility status
        $minPointsNeeded = Reward::min('pointsNeeded');
        $customer->eligibleForRewards = $customer->points >= ($minPointsNeeded ?? 0);
        $customer->save();

        return response()->json($customer, Response::HTTP_OK);
    }

    /**
     * Find a customer by phone number or create a new one
     */
    public function findOrCreate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'contactNum' => 'required|string|max:20'
        ]);

        $customer = Customer::where('contactNum', $validated['contactNum'])->first();
        
        if (!$customer) {
            // Get the minimum points needed for any reward
            $minPointsNeeded = Reward::min('pointsNeeded');
            
            $customer = Customer::create([
                'name' => $validated['name'],
                'contactNum' => $validated['contactNum'],
                'points' => 0,
                'eligibleForRewards' => 0 >= ($minPointsNeeded ?? 1) // Will be false since 0 points < min required
            ]);
            
            return response()->json([
                'customer' => $customer,
                'message' => 'New customer created successfully.'
            ], Response::HTTP_CREATED);
        }
        
        // Update eligibility status for existing customer
        $minPointsNeeded = Reward::min('pointsNeeded');
        $customer->eligibleForRewards = $customer->points >= ($minPointsNeeded ?? 0);
        $customer->save();

        return response()->json([
            'customer' => $customer,
            'message' => 'Existing customer found.'
        ], Response::HTTP_OK);
    }

    /**
     * Get available rewards for a customer
     */
    public function availableRewards(Customer $customer)
    {
        // Update eligibility status before checking
        $minPointsNeeded = Reward::min('pointsNeeded');
        $customer->eligibleForRewards = $customer->points >= ($minPointsNeeded ?? 0);
        $customer->save();
        
        if (!$customer->eligibleForRewards) {
            return response()->json([
                'message' => 'Customer is not eligible for rewards',
                'rewards' => []
            ], Response::HTTP_OK);
        }

        $availableRewards = Reward::where('pointsNeeded', '<=', $customer->points)
            ->orderBy('pointsNeeded', 'desc')
            ->get();

        return response()->json([
            'customer_points' => $customer->points,
            'rewards' => $availableRewards
        ], Response::HTTP_OK);
    }
} 