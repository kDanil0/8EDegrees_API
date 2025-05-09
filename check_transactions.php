<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Check if there are transactions
    $transactionCount = DB::table('transactions')->count();
    echo "Transaction count: " . $transactionCount . PHP_EOL;

    // Check if there are transaction items
    $transactionItemCount = DB::table('transaction_items')->count();
    echo "Transaction Item count: " . $transactionItemCount . PHP_EOL;

    // Check if there are products
    $productCount = DB::table('products')->count();
    echo "Product count: " . $productCount . PHP_EOL;

    // Show sample transactions if any
    if ($transactionCount > 0) {
        $transactions = DB::table('transactions')->limit(3)->get();
        echo "Sample transactions:\n";
        print_r($transactions);
    }

    // Show sample transaction items if any
    if ($transactionItemCount > 0) {
        $items = DB::table('transaction_items')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->select('transaction_items.*', 'products.name as product_name')
            ->limit(3)
            ->get();
        echo "Sample transaction items:\n";
        print_r($items);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
} 