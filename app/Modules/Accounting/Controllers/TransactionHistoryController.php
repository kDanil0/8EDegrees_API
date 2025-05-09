<?php

namespace App\Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionHistoryController extends Controller
{
    /**
     * Display a listing of the transactions.
     */
    public function index(Request $request)
    {
        // Parse filter parameters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');
        $customerId = $request->input('customer_id');
        
        // Start with a base query
        $query = Transaction::with(['customer', 'items.product', 'discount']);
        
        // Apply filters
        if ($startDate && $endDate) {
            $query->whereBetween('timestamp', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        
        // Get transactions ordered by most recent first
        $transactions = $query->orderBy('timestamp', 'desc')->get();
        
        return response()->json($transactions, Response::HTTP_OK);
    }

    /**
     * Display the specified transaction with details.
     */
    public function show(Transaction $transaction)
    {
        return response()->json(
            $transaction->load(['customer', 'items.product', 'discount']), 
            Response::HTTP_OK
        );
    }

    /**
     * Process a refund for a transaction.
     * This will update transaction status to refunded but won't revert inventory.
     */
    public function refund(Request $request, Transaction $transaction)
    {
        // Validate request
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        // Check if transaction can be refunded (not already refunded or canceled)
        if ($transaction->status !== Transaction::STATUS_COMPLETED) {
            return response()->json([
                'error' => 'Transaction cannot be refunded because it is already ' . $transaction->status
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();

        try {
            // Update transaction status
            $transaction->status = Transaction::STATUS_REFUNDED;
            $transaction->save();
            
            // If customer exists, revert loyalty points
            if ($transaction->customer_id) {
                $customer = Customer::find($transaction->customer_id);
                
                // Get configurable points exchange rate
                $exchangeRateConfig = \App\Models\SystemConfig::where('key', 'points_exchange_rate')->first();
                
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
                
                // Calculate points that were earned and revert them
                $pointsEarned = floor($transaction->total_amount * $exchangeRate['points'] / $exchangeRate['php_amount']);
                $customer->points -= $pointsEarned;
                if ($customer->points < 0) {
                    $customer->points = 0;
                }
                
                // Update eligibility
                $minPointsNeeded = \App\Models\Reward::min('pointsNeeded');
                $customer->eligibleForRewards = $customer->points >= ($minPointsNeeded ?? 0);
                
                $customer->save();
            }

            DB::commit();

            return response()->json([
                'transaction' => $transaction->load(['customer', 'items.product', 'discount']),
                'message' => 'Transaction refunded successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Process a cancellation for a transaction.
     * This will update transaction status to canceled and revert inventory.
     */
    public function cancel(Request $request, Transaction $transaction)
    {
        // Validate request
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        // Check if transaction can be canceled (not already refunded or canceled)
        if ($transaction->status !== Transaction::STATUS_COMPLETED) {
            return response()->json([
                'error' => 'Transaction cannot be canceled because it is already ' . $transaction->status
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();

        try {
            // Update transaction status
            $transaction->status = Transaction::STATUS_CANCELED;
            $transaction->save();
            
            // Revert inventory for each item
            foreach ($transaction->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->quantity += $item->quantity;
                    $product->save();
                }
            }
            
            // If customer exists, revert loyalty points
            if ($transaction->customer_id) {
                $customer = Customer::find($transaction->customer_id);
                
                // Get configurable points exchange rate
                $exchangeRateConfig = \App\Models\SystemConfig::where('key', 'points_exchange_rate')->first();
                
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
                
                // Calculate points that were earned and revert them
                $pointsEarned = floor($transaction->total_amount * $exchangeRate['points'] / $exchangeRate['php_amount']);
                $customer->points -= $pointsEarned;
                if ($customer->points < 0) {
                    $customer->points = 0;
                }
                
                // Update eligibility
                $minPointsNeeded = \App\Models\Reward::min('pointsNeeded');
                $customer->eligibleForRewards = $customer->points >= ($minPointsNeeded ?? 0);
                
                $customer->save();
            }

            DB::commit();

            return response()->json([
                'transaction' => $transaction->load(['customer', 'items.product', 'discount']),
                'message' => 'Transaction canceled successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 