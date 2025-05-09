<?php

namespace App\Modules\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::with(['customer', 'items.product'])->get();
        return response()->json($transactions, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'total_amount' => 'required|numeric',
            'timestamp' => 'required|date',
            'is_discount' => 'required|boolean',
            'payment_mode' => 'sometimes|string|in:cash,ewallet',
            'reference_number' => 'nullable|required_if:payment_mode,ewallet|string',
        ]);

        $transaction = Transaction::create($validated);

        return response()->json($transaction, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        return response()->json($transaction->load(['customer', 'items.product']), Response::HTTP_OK);
    }

    /**
     * Process a complete transaction with multiple items.
     */
    public function processTransaction(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
            'items.*.discount' => 'nullable|numeric',
            'is_discount' => 'required|boolean',
            'payment_mode' => 'required|string|in:cash,ewallet',
            'reference_number' => 'nullable|required_if:payment_mode,ewallet|string',
            'tendered_cash' => 'required_if:payment_mode,cash|numeric|min:0',
            'discount_id' => 'nullable|exists:discounts,id',
            'reward_id' => 'nullable|exists:rewards,id',
        ]);

        DB::beginTransaction();

        try {
            // Calculate total amount
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['price'];
                $discount = $item['discount'] ?? 0;
                $totalAmount += ($subtotal - $discount);
            }
            
            // Initialize variables for reward handling
            $appliedReward = null;
            $rewardDiscountAmount = 0;
            $freeItemProduct = null;
            $pointsEarned = 0;
            
            // Process reward if provided
            if (!empty($validated['reward_id']) && !empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                $reward = \App\Models\Reward::with('product')->find($validated['reward_id']);
                
                if ($reward && $customer) {
                    // Check if customer has enough points and the reward is active
                    if ($customer->points >= $reward->pointsNeeded && $reward->is_active) {
                        $appliedReward = $reward;
                        
                        // Handle different reward types
                        switch ($reward->type) {
                            case 'percentage_discount':
                                // Apply percentage discount
                                $rewardDiscountAmount = $totalAmount * ($reward->value / 100);
                                break;
                                
                            case 'free_item':
                                // Add free item to cart with 100% discount
                                if ($reward->product) {
                                    $freeItemProduct = $reward->product;
                                    
                                    // Create a transaction item for the free product
                                    $freeItemData = [
                                        'product_id' => $freeItemProduct->id,
                                        'quantity' => 1,
                                        'price' => $freeItemProduct->price,
                                        'discount' => $freeItemProduct->price, // Full discount
                                        'is_free_item' => true, // Mark as free item
                                    ];
                                    
                                    // Add to validated items for later processing
                                    $validated['items'][] = $freeItemData;
                                }
                                break;
                                
                            default:
                                // Legacy fixed-amount discount (fallback for old rewards)
                                $rewardDiscountAmount = $reward->pointsNeeded;
                        }
                        
                        // Redeem the reward
                        \App\Models\RewardsHistory::create([
                            'customer_id' => $customer->id,
                            'reward_id' => $reward->id,
                        ]);
                        
                        // Deduct points
                        $customer->points -= $reward->pointsNeeded;
                        
                        // Get the minimum points needed for any reward
                        $minPointsNeeded = \App\Models\Reward::min('pointsNeeded');
                        
                        // Check if customer is still eligible for rewards
                        $customer->eligibleForRewards = $customer->points >= ($minPointsNeeded ?? 0);
                        $customer->save();
                    }
                }
            }
            
            // Apply percentage discount if discount_id is provided
            $discountAmount = 0;
            if (!empty($validated['discount_id'])) {
                $discount = \App\Models\Discount::find($validated['discount_id']);
                if ($discount) {
                    $discountAmount = $totalAmount * ($discount->percentage / 100);
                }
            }
            
            // Calculate final total after all discounts
            $finalTotal = $totalAmount - $discountAmount - $rewardDiscountAmount;
            $finalTotal = max($finalTotal, 0); // Ensure total doesn't go negative

            // Create transaction
            $transaction = Transaction::create([
                'customer_id' => $validated['customer_id'] ?? null,
                'product_id' => $validated['items'][0]['product_id'], // Set first product as reference
                'total_amount' => $finalTotal,
                'timestamp' => now(),
                'is_discount' => $validated['is_discount'] || !empty($validated['discount_id']) || !empty($appliedReward),
                'payment_mode' => $validated['payment_mode'],
                'reference_number' => $validated['reference_number'] ?? null,
                'discount_id' => $validated['discount_id'] ?? null,
                'reward_id' => $appliedReward ? $appliedReward->id : null,
                'status' => Transaction::STATUS_COMPLETED,
            ]);

            // Create transaction items
            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['price'];
                $discount = $item['discount'] ?? 0;
                $isFreeItem = isset($item['is_free_item']) && $item['is_free_item'];

                $transactionItem = TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'is_free_item' => $isFreeItem,
                ]);

                // Update product quantity (skip for free items if needed)
                if (!$isFreeItem) {
                    $product = Product::find($item['product_id']);
                    $product->quantity -= $item['quantity'];
                    $product->save();
                }
            }

            // Add loyalty points if customer is provided
            if ($validated['customer_id']) {
                $customer = $customer ?? Customer::find($validated['customer_id']);
                
                // Get configurable points exchange rate
                $exchangeRateConfig = SystemConfig::where('key', 'points_exchange_rate')->first();
                
                // Set default values if not found
                $exchangeRate = [
                    'php_amount' => 100,
                    'points' => 10
                ];
                
                // Parse the exchange rate from config if available
                if ($exchangeRateConfig) {
                    $configValue = json_decode($exchangeRateConfig->value, true);
                    if (is_array($configValue) && isset($configValue['php_amount']) && isset($configValue['points'])) {
                        $exchangeRate = $configValue;
                    }
                }
                
                // Calculate points using the exchange rate
                $pointsEarned = floor($finalTotal * $exchangeRate['points'] / $exchangeRate['php_amount']);
                
                $customer->points += $pointsEarned;
                
                // Get the minimum points needed for any reward
                $minPointsNeeded = \App\Models\Reward::min('pointsNeeded');
                
                // Check if customer is eligible for rewards based on points comparison
                $customer->eligibleForRewards = $customer->points >= ($minPointsNeeded ?? 0);
                
                $customer->save();
            }

            DB::commit();

            return response()->json([
                'transaction' => $transaction->load(['customer', 'items.product', 'discount']),
                'points_earned' => $pointsEarned ?? 0,
                'discount_amount' => $discountAmount,
                'reward_discount_amount' => $rewardDiscountAmount,
                'applied_reward' => $appliedReward,
                'free_item_product' => $freeItemProduct,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
} 