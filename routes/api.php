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
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user()->load('roles.permissions');
});


// AUTH ROUTES (public)
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {

    // Purchases
    Route::post('/purchases', [PurchaseController::class, 'store'])->middleware('permission:purchases.create');
    Route::get('/purchases', [PurchaseController::class, 'index'])->middleware('permission:purchases.view');
    Route::get('/purchases/{id}', [PurchaseController::class, 'show'])->middleware('permission:purchases.view');

    // Sales
    Route::post('/sales', [SaleController::class, 'store'])->middleware('permission:sales.create');
    Route::get('/sales', [SaleController::class, 'index'])->middleware('permission:sales.view');
    Route::get('/sales/{id}', [SaleController::class, 'show'])->middleware('permission:sales.view');
    Route::put('/sales/{id}', [SaleController::class, 'update'])->middleware('permission:sales.update');
    Route::patch('/sales/{id}', [SaleController::class, 'update'])->middleware('permission:sales.update');


    // Products
    Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.create');
    Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view');
    Route::get('/products/{id}', [ProductController::class, 'show'])->middleware('permission:products.view');
    Route::put('/products/{id}', [ProductController::class, 'update'])->middleware('permission:products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->middleware('permission:products.delete');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.view');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->middleware('permission:customers.view');
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->middleware('permission:customers.update');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('permission:customers.create');
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->middleware('permission:customers.delete');

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])->middleware('permission:suppliers.view');
    Route::get('/suppliers/{id}', [SupplierController::class, 'show'])->middleware('permission:suppliers.view');
    Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('permission:suppliers.create');
    Route::put('/suppliers/{id}', [SupplierController::class, 'update'])->middleware('permission:suppliers.update');
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers.delete');

    // Stock Movement
    Route::get('/stock-movements', [StockMovementController::class, 'index'])->middleware('permission:products.view');
    Route::get('/stock-movements/{id}', [StockMovementController::class, 'show'])->middleware('permission:products.view');

    Route::middleware(['permission:reports.view'])->group(function () {
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
        Route::get('/reports/profit-report', [ReportsController::class, 'profitReport']);
        Route::get('/reports/revenue-breakdown', [ReportsController::class, 'revenueBreakdown']);
    });

    // Users
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::get('/users/{id}', [UserController::class, 'show'])->middleware('permission:users.view');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('permission:users.update');
    Route::patch('/users/{id}', [UserController::class, 'update'])->middleware('permission:users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('permission:users.delete');

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create');
    Route::get('/roles/{id}', [RoleController::class, 'show'])->middleware('permission:roles.view');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->middleware('permission:roles.update');
    Route::patch('/roles/{id}', [RoleController::class, 'update'])->middleware('permission:roles.update');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view');
    Route::post('/permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.create');
    Route::get('/permissions/{id}', [PermissionController::class, 'show'])->middleware('permission:permissions.view');
    Route::put('/permissions/{id}', [PermissionController::class, 'update'])->middleware('permission:permissions.update');
    Route::patch('/permissions/{id}', [PermissionController::class, 'update'])->middleware('permission:permissions.update');
    Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete');
});
