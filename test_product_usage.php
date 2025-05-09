<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

try {
    echo "Testing product usage query...\n";
    
    $startDate = Carbon::now()->subDays(30)->toDateString();
    $endDate = Carbon::now()->toDateString();
    
    echo "Date range: $startDate to $endDate\n";
    
    // Let's manually run the product usage query
    $productUsage = DB::table('transaction_items')
        ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
        ->join('products', 'transaction_items.product_id', '=', 'products.id')
        ->join('categories', 'products.category_id', '=', 'categories.id')
        ->whereBetween('transactions.timestamp', [$startDate, $endDate])
        ->select(
            'products.id',
            'products.name',
            'categories.name as category_name',
            DB::raw('SUM(transaction_items.quantity) as total_quantity'),
            DB::raw('SUM(transaction_items.subtotal) as total_amount')
        )
        ->groupBy('products.id', 'products.name', 'categories.name')
        ->orderByDesc('total_quantity')
        ->get();
    
    echo "Query result count: " . count($productUsage) . "\n";
    
    if(count($productUsage) > 0) {
        echo "Product usage results:\n";
        print_r($productUsage);
    } else {
        echo "No results found. Let's debug:\n";
        
        // Check transactions in the date range
        $transactionsInRange = DB::table('transactions')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->get();
        
        echo "Transactions in date range: " . count($transactionsInRange) . "\n";
        
        if(count($transactionsInRange) > 0) {
            print_r($transactionsInRange);
            
            // Get the first transaction's id to check for items
            $transId = $transactionsInRange[0]->id;
            $items = DB::table('transaction_items')
                ->where('transaction_id', $transId)
                ->get();
            
            echo "Items for transaction $transId: " . count($items) . "\n";
            print_r($items);
        }
        
        // Check if all expected tables exist
        echo "Checking table structure:\n";
        
        // Check product to category relationships
        $productsWithCategories = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.id', 'products.name', 'categories.name as category_name')
            ->get();
        
        echo "Products with categories: " . count($productsWithCategories) . "\n";
        if(count($productsWithCategories) > 0) {
            print_r($productsWithCategories);
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
} 