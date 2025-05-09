<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

try {
    echo "Testing updated product usage query...\n";
    
    $startDate = Carbon::now()->subDays(30)->startOfDay()->toDateTimeString();
    $endDate = Carbon::now()->endOfDay()->toDateTimeString();
    
    echo "Date range: $startDate to $endDate\n";
    
    // Let's manually run the product usage query with the updated date range
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
        echo "No results found. Let's debug further:\n";
        
        // Check transactions in the date range with the updated range
        $transactionsInRange = DB::table('transactions')
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->get();
        
        echo "Transactions in date range: " . count($transactionsInRange) . "\n";
        
        if(count($transactionsInRange) > 0) {
            print_r($transactionsInRange);
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
} 