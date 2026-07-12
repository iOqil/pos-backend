<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceTransactionController;

use App\Http\Controllers\Api\ReportController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/verify-password', [AuthController::class, 'verifyPassword']);

    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        
        // Write access for products, categories, etc
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('brands', BrandController::class)->except(['index', 'show']);
        Route::delete('/products/bulk', [ProductController::class, 'bulkDestroy']);
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        Route::apiResource('services', ServiceController::class)->except(['index', 'show']);
        Route::post('/sales/{id}/refund', [SaleController::class, 'refund']);
        Route::apiResource('sales', SaleController::class)->only(['destroy']);
        Route::get('/reports/overview', [ReportController::class, 'overview']);
        Route::get('/reports/daily', [ReportController::class, 'daily']);
        Route::get('/reports/products', [ReportController::class, 'productSales']);
        Route::apiResource('inventory', \App\Http\Controllers\Api\InventoryController::class)->only(['store', 'update', 'destroy']);
    });

    // Mixed access (Admin + Cashier)
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('brands', BrandController::class)->only(['index', 'show']);
    Route::get('/products/barcode/{barcode}', [ProductController::class, 'getByBarcode']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::get('/customers/search', [CustomerController::class, 'search']);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('sales', SaleController::class)->only(['index', 'show', 'store']);
    Route::apiResource('services', ServiceController::class)->only(['index', 'show']);
    Route::apiResource('service-transactions', ServiceTransactionController::class)->only(['index', 'store']);
    Route::apiResource('inventory', \App\Http\Controllers\Api\InventoryController::class)->only(['index']);
});
