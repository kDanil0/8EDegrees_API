<?php

namespace App\Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\SalesReport;
use App\Models\CashDrawerOperation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get daily sales report.
     */
    public function dailySales(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        
        $sales = Transaction::whereDate('timestamp', $date)
            ->select(
                DB::raw('DATE(timestamp) as date'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw('DATE(timestamp)'))
            ->first();
        
        if (!$sales) {
            $sales = [
                'date' => $date,
                'total_sales' => 0,
                'transaction_count' => 0
            ];
        }
        
        // Get cash drawer operations for the day
        $cashDrawer = CashDrawerOperation::where('operation_date', $date)->first();
        
        if (!$cashDrawer) {
            $cashDrawer = [
                'cash_in' => 0,
                'cash_out' => 0,
                'cash_count' => 0,
                'expected_cash' => 0,
                'short_over' => 0
            ];
        }
        
        // Combine sales and cash drawer data
        $response = [
            'date' => $date,
            'total_sales' => $sales['total_sales'],
            'cash_sales' => $sales['total_sales'], // Assuming all sales are cash for now
            'transaction_count' => $sales['transaction_count'],
            'cash_in' => $cashDrawer['cash_in'],
            'cash_out' => $cashDrawer['cash_out'],
            'expected_cash' => $cashDrawer['expected_cash'] ?: $sales['total_sales'] + $cashDrawer['cash_in'] - $cashDrawer['cash_out'],
            'cash_count' => $cashDrawer['cash_count'],
            'short_over' => $cashDrawer['short_over'] ?: ($cashDrawer['cash_count'] - ($sales['total_sales'] + $cashDrawer['cash_in'] - $cashDrawer['cash_out']))
        ];
        
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Get monthly sales report.
     */
    public function monthlySales(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        $dailySales = Transaction::whereBetween('timestamp', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(timestamp) as date'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw('DATE(timestamp)'))
            ->get();
        
        // Get purchase order total for the month
        $purchaseOrderTotal = DB::table('purchase_orders')
            ->whereDate('orderDate', '>=', $startDate)
            ->whereDate('orderDate', '<=', $endDate)
            ->sum('totalAmount');
        
        $monthlySummary = [
            'year' => $year,
            'month' => $month,
            'total_sales' => $dailySales->sum('total_sales'),
            'total_purchase_orders' => $purchaseOrderTotal,
            'transaction_count' => $dailySales->sum('transaction_count'),
            'daily_breakdown' => $dailySales
        ];
        
        return response()->json($monthlySummary, Response::HTTP_OK);
    }

    /**
     * Get yearly sales report.
     */
    public function yearlySales(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear();
        
        $monthlySales = Transaction::whereBetween('timestamp', [$startDate, $endDate])
            ->select(
                DB::raw('MONTH(timestamp) as month'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy(DB::raw('MONTH(timestamp)'))
            ->get();
        
        // Get total sales for the year
        $totalSales = $monthlySales->sum('total_sales');
        
        // Get purchase order total for the year
        $purchaseOrderTotal = DB::table('purchase_orders')
            ->whereDate('orderDate', '>=', $startDate)
            ->whereDate('orderDate', '<=', $endDate)
            ->sum('totalAmount');
        
        // Calculate average monthly sales - if no sales, average is 0
        $nonZeroMonthsCount = $monthlySales->count();
        $averageMonthlySales = $nonZeroMonthsCount > 0 ? ($totalSales / $nonZeroMonthsCount) : 0;
        
        $yearlySummary = [
            'year' => $year,
            'total_sales' => $totalSales,
            'total_purchase_orders' => $purchaseOrderTotal,
            'transaction_count' => $monthlySales->sum('transaction_count'),
            'average_monthly_sales' => $averageMonthlySales,
            'monthly_breakdown' => $monthlySales
        ];
        
        return response()->json($yearlySummary, Response::HTTP_OK);
    }

    /**
     * Get product usage report.
     */
    public function productUsage(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->startOfDay()->toDateTimeString());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay()->toDateTimeString());
        
        // Debug statement
        \Log::info("ProductUsage date range: $startDate to $endDate");
        
        $productUsage = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
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
        
        // Debug statement
        \Log::info("ProductUsage results count: " . count($productUsage));
        
        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'products' => $productUsage
        ], Response::HTTP_OK);
    }

    /**
     * Get sales by product.
     */
    public function salesByProduct(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->startOfDay()->toDateTimeString());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay()->toDateTimeString());
        $limit = $request->input('limit', null);
        
        // Debug statement
        \Log::info("SalesByProduct date range: $startDate to $endDate, limit: " . ($limit ?: 'none'));
        
        $query = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('transactions.timestamp', [$startDate, $endDate])
            ->select(
                'products.id',
                'products.name',
                'categories.name as category_name',
                DB::raw('SUM(transaction_items.quantity) as total_quantity'),
                DB::raw('SUM(transaction_items.subtotal) as total_sales'),
                DB::raw('ROUND(AVG(transaction_items.price), 2) as average_price')
            )
            ->groupBy('products.id', 'products.name', 'categories.name')
            ->orderByDesc('total_quantity');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        $salesByProduct = $query->get();
        
        // Debug statement
        \Log::info("SalesByProduct results count: " . count($salesByProduct));
        if (count($salesByProduct) > 0) {
            \Log::info("First product from results: " . json_encode($salesByProduct[0]));
        }
        
        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'products' => $salesByProduct
        ], Response::HTTP_OK);
    }
    
    /**
     * Get sales summary including total sales and units sold.
     */
    public function salesSummary(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->startOfDay()->toDateTimeString());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay()->toDateTimeString());
        
        // Debug statement
        \Log::info("SalesSummary date range: $startDate to $endDate");
        
        // Get total sales amount
        $totalSales = Transaction::whereBetween('timestamp', [$startDate, $endDate])
            ->sum('total_amount');
            
        // Get total units sold
        $unitsSold = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.timestamp', [$startDate, $endDate])
            ->sum('transaction_items.quantity');
            
        // Get completed transaction count
        $completedTransactions = Transaction::whereBetween('timestamp', [$startDate, $endDate])
            ->count();
            
        // Debug statement
        \Log::info("SalesSummary results: Sales=$totalSales, Units=$unitsSold, Transactions=$completedTransactions");
            
        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_sales' => $totalSales,
            'units_sold' => $unitsSold,
            'completed_transactions' => $completedTransactions
        ], Response::HTTP_OK);
    }

    /**
     * Get cash drawer operations for a specific date
     */
    public function getCashDrawerOperations(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        
        $cashDrawer = CashDrawerOperation::where('operation_date', $date)->first();
        
        if (!$cashDrawer) {
            // Get the total sales for the day to calculate expected cash
            $totalSales = Transaction::whereDate('timestamp', $date)
                ->sum('total_amount');
            
            $cashDrawer = [
                'operation_date' => $date,
                'cash_in' => 0,
                'cash_out' => 0,
                'cash_count' => 0,
                'expected_cash' => $totalSales,
                'short_over' => 0
            ];
        }
        
        return response()->json($cashDrawer, Response::HTTP_OK);
    }
    
    /**
     * Update cash drawer operations for a specific date
     */
    public function updateCashDrawerOperations(Request $request)
    {
        $validated = $request->validate([
            'operation_date' => 'required|date',
            'cash_in' => 'required|numeric',
            'cash_out' => 'required|numeric',
            'cash_count' => 'required|numeric',
            'notes' => 'nullable|string'
        ]);
        
        // Get total sales for the date
        $totalSales = Transaction::whereDate('timestamp', $validated['operation_date'])
            ->sum('total_amount');
        
        // Calculate expected cash and short/over
        $expectedCash = $totalSales + $validated['cash_in'] - $validated['cash_out'];
        $shortOver = $validated['cash_count'] - $expectedCash;
        
        $validated['expected_cash'] = $expectedCash;
        $validated['short_over'] = $shortOver;
        
        $cashDrawer = CashDrawerOperation::updateOrCreate(
            ['operation_date' => $validated['operation_date']],
            $validated
        );
        
        return response()->json($cashDrawer, Response::HTTP_OK);
    }
} 