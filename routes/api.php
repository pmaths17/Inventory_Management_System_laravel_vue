<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


// AUTH ROUTES (public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {

    // Purchases
    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::get('/purchases', [PurchaseController::class, 'index']);
    Route::get('/purchases/{id}', [PurchaseController::class, 'show']);

    // Sales
    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales', [SaleController::class, 'index']);
    Route::get('/sales/{id}', [SaleController::class, 'show']);
    Route::put('/sales/{id}', [SaleController::class, 'update']);   // full update
    Route::patch('/sales/{id}', [SaleController::class, 'update']); // partial update


    // Products
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Customers
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
    Route::post('/customers', [CustomerController::class, 'store']);
    // routes/api.php
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);

    // Stock Movement
    Route::get('/stock-movements', [StockMovementController::class, 'index']);
    Route::get('/stock-movements/{id}', [StockMovementController::class, 'show']);

    Route::middleware(['admin'])->group(function () {
        // Reports
        Route::get('/reports/stock-summary', [ReportsController::class, 'stockSummary']);
        Route::get('/reports/stock-ledger', [ReportsController::class, 'stockLedger']);
        Route::get('/reports/low-stock', [ReportsController::class, 'lowStock']);
        Route::get('/reports/purchases', [ReportsController::class, 'purchaseList']);
        Route::get('/reports/purchases/{id}', [ReportsController::class, 'purchaseDetails']);
        Route::get('/reports/supplier-wise-purchases', [ReportsController::class, 'supplierWisePurchases']);
        Route::get('/reports/sales', [ReportsController::class, 'salesList']);
        Route::get('/reports/sales/{id}', [ReportsController::class, 'salesdetails']);
        Route::get('/reports/customer-wise-sales', [ReportsController::class, 'customerWiseSales']);
        Route::get('/reports/product-wise-sales', [ReportsController::class, 'productWiseSales']);
        Route::get('/reports/revenue', [ReportsController::class, 'revenue']);
        Route::get('/reports/purchase-cost', [ReportsController::class, 'purchaseCost']);
        Route::get('/reports/profit', [ReportsController::class, 'profit']);
        Route::get('/reports/sales-chart', [ReportsController::class, 'salesChartData']);
        Route::get('/reports/dashboard-summary', [ReportsController::class, 'dashboardSummary']);
        Route::apiResource('users', UserController::class);
        Route::get('/reports/profit-report', [ReportsController::class, 'profitReport']);
        Route::get('/reports/revenue-breakdown', [ReportsController::class, 'revenueBreakdown']);
    });
});
