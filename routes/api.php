<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Inventory\Controllers\ProductController;
use App\Modules\Inventory\Controllers\CategoryController;
use App\Modules\SupplyChain\Controllers\SupplierController;
use App\Modules\SupplyChain\Controllers\PurchaseOrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Modules\Accounting\Controllers\TransactionHistoryController;

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// // Public routes
// Route::get('feedback', 'App\Modules\Customer\Controllers\FeedbackController@index');
// Route::post('feedback', 'App\Modules\Customer\Controllers\FeedbackController@store');

// Inventory Subsystem
Route::prefix('inventory')->group(function () {
    // IMPORTANT: Custom routes must be defined BEFORE the resource routes
    Route::get('products/low-stock', [ProductController::class, 'lowStock']);
    Route::get('products/expiration-report', [ProductController::class, 'expirationReport']);
    Route::get('products/deleted', [ProductController::class, 'deleted']);
    Route::post('products/{id}/restore', [ProductController::class, 'restore']);
    
    // Using class references instead of strings
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    // Get products by category
    Route::get('categories/{category}/products', [CategoryController::class, 'getProducts']);
});

// Supply Chain Subsystem
Route::prefix('supply-chain')->group(function () {
    Route::apiResource('suppliers', SupplierController::class);
    Route::get('purchase-orders/delivery-history', [PurchaseOrderController::class, 'deliveryHistory']);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveOrder']);
    Route::post('purchase-orders/{purchaseOrder}/discrepancies', [PurchaseOrderController::class, 'recordDiscrepancies']);
});

// Customer Management Subsystem
Route::prefix('customer')->group(function () {
    Route::apiResource('customers', 'App\Modules\Customer\Controllers\CustomerController');
    Route::apiResource('rewards', 'App\Modules\Customer\Controllers\RewardController');
    Route::get('rewards-active', ['App\Modules\Customer\Controllers\RewardController', 'getActiveRewards']);
    Route::apiResource('discounts', \App\Modules\Customer\Controllers\DiscountController::class);
    Route::get('discounts-active', [App\Modules\Customer\Controllers\DiscountController::class, 'getActiveDiscounts']);
    Route::get('rewards-history', 'App\Modules\Customer\Controllers\RewardsHistoryController@index');
    Route::get('customers/{customer}/rewards-history', 'App\Modules\Customer\Controllers\CustomerController@rewardsHistory');
    Route::post('customers/{customer}/add-points', 'App\Modules\Customer\Controllers\CustomerController@addPoints');
    Route::post('customers/{customer}/redeem-reward', 'App\Modules\Customer\Controllers\CustomerController@redeemReward');
    Route::get('customers/{customer}/available-rewards', 'App\Modules\Customer\Controllers\CustomerController@availableRewards');
    Route::get('customers/search', 'App\Modules\Customer\Controllers\CustomerController@search');
    Route::post('customers/find-or-create', 'App\Modules\Customer\Controllers\CustomerController@findOrCreate');
    Route::get('feedback', 'App\Modules\Customer\Controllers\FeedbackController@index');
    Route::post('feedback', 'App\Modules\Customer\Controllers\FeedbackController@store');
    
    // System Configuration routes
    Route::get('config', 'App\Modules\Customer\Controllers\SystemConfigController@index');
    Route::get('config/points-exchange-rate', 'App\Modules\Customer\Controllers\SystemConfigController@getPointsExchangeRate');
    Route::put('config/points-exchange-rate', 'App\Modules\Customer\Controllers\SystemConfigController@updatePointsExchangeRate');
    Route::get('config/{key}', 'App\Modules\Customer\Controllers\SystemConfigController@show');
    Route::put('config/{key}', 'App\Modules\Customer\Controllers\SystemConfigController@update');
});

// Accounting Subsystem
Route::prefix('accounting')->group(function () {
    Route::get('reports/sales/daily', 'App\Modules\Accounting\Controllers\ReportController@dailySales');
    Route::get('reports/sales/monthly', 'App\Modules\Accounting\Controllers\ReportController@monthlySales');
    Route::get('reports/sales/yearly', 'App\Modules\Accounting\Controllers\ReportController@yearlySales');
    Route::get('reports/product-usage', 'App\Modules\Accounting\Controllers\ReportController@productUsage');
    Route::get('reports/sales-by-product', 'App\Modules\Accounting\Controllers\ReportController@salesByProduct');
    Route::get('reports/sales/summary', 'App\Modules\Accounting\Controllers\ReportController@salesSummary');
    Route::get('cash-drawer', 'App\Modules\Accounting\Controllers\ReportController@getCashDrawerOperations');
    Route::post('cash-drawer', 'App\Modules\Accounting\Controllers\ReportController@updateCashDrawerOperations');
    
    // Transaction History routes
    Route::get('transactions', [TransactionHistoryController::class, 'index']);
    Route::get('transactions/{transaction}', [TransactionHistoryController::class, 'show']);
    Route::post('transactions/{transaction}/refund', [TransactionHistoryController::class, 'refund']);
    Route::post('transactions/{transaction}/cancel', [TransactionHistoryController::class, 'cancel']);
});

// POS routes
Route::prefix('pos')->group(function () {
    Route::apiResource('transactions', \App\Modules\POS\Controllers\TransactionController::class);
    Route::post('transactions/process', [App\Modules\POS\Controllers\TransactionController::class, 'processTransaction']);
});

// User Management Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});
