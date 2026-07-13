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
        
        // Admin only delete routes
        Route::apiResource('categories', CategoryController::class)->only(['destroy']);
        Route::apiResource('brands', BrandController::class)->only(['destroy']);
        Route::delete('/products/bulk', [ProductController::class, 'bulkDestroy']);
        Route::apiResource('products', ProductController::class)->only(['destroy']);
        Route::apiResource('services', ServiceController::class)->only(['destroy']);
        
        Route::apiResource('sales', SaleController::class)->only(['destroy']);
        
        Route::get('/reports/overview', [ReportController::class, 'overview']);
        Route::get('/reports/daily', [ReportController::class, 'daily']);
        Route::get('/reports/products', [ReportController::class, 'productSales']);
        
        Route::apiResource('inventory', \App\Http\Controllers\Api\InventoryController::class)->only(['destroy']);

        // Nasiya (Debts)
        Route::get('/debts', [\App\Http\Controllers\Api\DebtController::class, 'index']);
        Route::post('/debts', [\App\Http\Controllers\Api\DebtController::class, 'store']);
        Route::post('/debts/{id}/pay', [\App\Http\Controllers\Api\DebtController::class, 'pay']);
        Route::delete('/debts/{id}', [\App\Http\Controllers\Api\DebtController::class, 'destroy']);

        // Telegram sozlamalari
        Route::get('/telegram/settings', [\App\Http\Controllers\Api\TelegramController::class, 'getSettings']);
        Route::post('/telegram/settings', [\App\Http\Controllers\Api\TelegramController::class, 'saveSettings']);
        Route::post('/telegram/test', [\App\Http\Controllers\Api\TelegramController::class, 'test']);

        // Mijozlarni o'chirish (faqat admin)
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    });

    // Mixed access (Admin + Cashier)
    Route::apiResource('categories', CategoryController::class)->except(['destroy']);
    Route::apiResource('brands', BrandController::class)->except(['destroy']);
    Route::get('/products/barcode/{barcode}', [ProductController::class, 'getByBarcode']);
    Route::apiResource('products', ProductController::class)->except(['destroy']);
    
    Route::get('/customers/search', [CustomerController::class, 'search']);
    Route::apiResource('customers', CustomerController::class)->except(['destroy']);
    
    Route::post('/sales/{id}/refund', [SaleController::class, 'refund']);
    Route::apiResource('sales', SaleController::class)->except(['destroy']);
    
    Route::apiResource('services', ServiceController::class)->except(['destroy']);
    Route::apiResource('service-transactions', ServiceTransactionController::class)->only(['index', 'store']);
    Route::apiResource('inventory', \App\Http\Controllers\Api\InventoryController::class)->except(['destroy']);
});
