<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

try {
    // Get all transactions with their dates
    $transactions = DB::table('transactions')
        ->select('id', 'timestamp', 'total_amount')
        ->orderBy('timestamp')
        ->get();
    
    echo "All transactions with dates:\n";
    foreach ($transactions as $t) {
        echo "ID: {$t->id}, Date: {$t->timestamp}, Amount: {$t->total_amount}\n";
    }
    
    // Current date from Carbon
    $today = Carbon::now()->toDateString();
    $thirtyDaysAgo = Carbon::now()->subDays(30)->toDateString();
    
    echo "\nCurrent date range in the system:\n";
    echo "Today: {$today}\n";
    echo "30 days ago: {$thirtyDaysAgo}\n";
    
    // Now try a custom date range that includes the transactions
    echo "\nTrying a custom date range that includes all transactions:\n";
    $customStart = '2025-01-01';
    $customEnd = '2026-01-01';
    
    $customRangeTransactions = DB::table('transactions')
        ->whereBetween('timestamp', [$customStart, $customEnd])
        ->count();
    
    echo "Transactions between {$customStart} and {$customEnd}: {$customRangeTransactions}\n";
    
    // Check if the timestamp column is properly formatted
    $firstTransaction = DB::table('transactions')->first();
    if ($firstTransaction) {
        echo "\nFirst transaction timestamp format check:\n";
        echo "Raw timestamp: {$firstTransaction->timestamp}\n";
        
        // Try to parse the timestamp
        try {
            $parsedDate = Carbon::parse($firstTransaction->timestamp);
            echo "Parsed as Carbon: {$parsedDate->toDateTimeString()}\n";
            echo "Year: {$parsedDate->year}, Month: {$parsedDate->month}, Day: {$parsedDate->day}\n";
        } catch (Exception $e) {
            echo "Failed to parse timestamp: {$e->getMessage()}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
} 