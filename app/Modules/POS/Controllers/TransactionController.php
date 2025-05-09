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
            
            // Apply percentage discount if discount_id is provided
            $discountAmount = 0;
            if (!empty($validated['discount_id'])) {
                $discount = \App\Models\Discount::find($validated['discount_id']);
                if ($discount) {
                    $discountAmount = $totalAmount * ($discount->percentage / 100);
                    $totalAmount = $totalAmount - $discountAmount;
                }
            }

            // Create transaction
            $transaction = Transaction::create([
                'customer_id' => $validated['customer_id'] ?? null,
                'product_id' => $validated['items'][0]['product_id'], // Set first product as reference
                'total_amount' => $totalAmount,
                'timestamp' => now(),
                'is_discount' => $validated['is_discount'],
                'payment_mode' => $validated['payment_mode'],
                'reference_number' => $validated['reference_number'] ?? null,
                'discount_id' => $validated['discount_id'] ?? null,
                'status' => Transaction::STATUS_COMPLETED,
            ]);

            // Create transaction items
            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['price'];
                $discount = $item['discount'] ?? 0;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                ]);

                // Update product quantity
                $product = Product::find($item['product_id']);
                $product->quantity -= $item['quantity'];
                $product->save();
            }

            // Add loyalty points if customer is provided
            if ($validated['customer_id']) {
                $customer = Customer::find($validated['customer_id']);
                
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
                $pointsEarned = floor($totalAmount * $exchangeRate['points'] / $exchangeRate['php_amount']);
                
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