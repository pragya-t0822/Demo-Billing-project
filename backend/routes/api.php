<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Billing\InvoiceController;
use App\Http\Controllers\Api\Accounting\JournalEntryController;
use App\Http\Controllers\Api\Inventory\StockController;
use App\Http\Controllers\Api\Reconciliation\ReconciliationController;
use App\Http\Controllers\Api\Recovery\RecoveryController;
use App\Http\Controllers\Api\Reporting\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Retail Billing & Accounting System
|--------------------------------------------------------------------------
| All routes use JWT authentication via Sanctum/JWTAuth.
| RBAC enforced via 'role:' middleware (spatie/laravel-permission).
|--------------------------------------------------------------------------
*/

// ── Public: Authentication ───────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('login',   [AuthController::class, 'login']);
    Route::post('logout',  [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('me',       [AuthController::class, 'me'])->middleware('auth:api');
});

// ── Protected: All routes require JWT authentication ─────────────────────
Route::middleware(['auth:api', 'audit'])->group(function () {

    // ── Billing Module ───────────────────────────────────────────────────
    Route::prefix('invoices')->middleware('role:SUPER_ADMIN,STORE_MANAGER,CASHIER')->group(function () {
        Route::get('/',               [InvoiceController::class, 'index']);
        Route::post('/',              [InvoiceController::class, 'store']);
        Route::get('/{id}',           [InvoiceController::class, 'show']);
        Route::put('/{id}/confirm',   [InvoiceController::class, 'confirm']);
        Route::post('/{id}/payment',  [InvoiceController::class, 'payment']);
        Route::post('/{id}/cancel',   [InvoiceController::class, 'cancel'])
            ->middleware('role:SUPER_ADMIN,STORE_MANAGER'); // cashier cannot cancel
    });

    // ── Accounting Module ────────────────────────────────────────────────
    Route::prefix('accounting')->middleware('role:SUPER_ADMIN,STORE_MANAGER,ACCOUNTANT,AUDITOR')->group(function () {
        Route::post('journal-entries',              [JournalEntryController::class, 'store'])
            ->middleware('role:SUPER_ADMIN,ACCOUNTANT');
        Route::get('journal-entries/{id}',          [JournalEntryController::class, 'show']);
        Route::post('journal-entries/{id}/reverse', [JournalEntryController::class, 'reverse'])
            ->middleware('role:SUPER_ADMIN,ACCOUNTANT');
        Route::get('ledger/{accountCode}',          [JournalEntryController::class, 'ledger']);
        Route::get('trial-balance',                 [JournalEntryController::class, 'trialBalance']);
    });

    // ── Inventory Module ─────────────────────────────────────────────────
    Route::prefix('inventory')->middleware('role:SUPER_ADMIN,STORE_MANAGER,CASHIER')->group(function () {
        Route::get('low-stock',           [StockController::class, 'lowStock']);
        Route::get('metal-rate/{type}',   [StockController::class, 'getMetalRate']);
        Route::post('metal-rate',         [StockController::class, 'setMetalRate'])
            ->middleware('role:SUPER_ADMIN,STORE_MANAGER');
        Route::post('weight-price',       [StockController::class, 'calculateWeightPrice']);
        Route::post('adjustment',         [StockController::class, 'adjustment'])
            ->middleware('role:SUPER_ADMIN,STORE_MANAGER');
        Route::get('{sku}',               [StockController::class, 'show']);
    });

    // ── Reconciliation Module ────────────────────────────────────────────
    Route::prefix('reconciliation')->middleware('role:SUPER_ADMIN,STORE_MANAGER,ACCOUNTANT')->group(function () {
        Route::post('import',             [ReconciliationController::class, 'import']);
        Route::post('run',                [ReconciliationController::class, 'run']);
        Route::get('unmatched',           [ReconciliationController::class, 'unmatched']);
    });

    Route::prefix('settlements')->middleware('role:SUPER_ADMIN,STORE_MANAGER,ACCOUNTANT')->group(function () {
        Route::post('{id}/process',       [ReconciliationController::class, 'processSettlement']);
    });

    // ── Recovery Module ──────────────────────────────────────────────────
    Route::prefix('recovery')->middleware('role:SUPER_ADMIN,STORE_MANAGER,RECOVERY_AGENT')->group(function () {
        Route::get('overdue',             [RecoveryController::class, 'overdue']);
        Route::post('run-cycle',          [RecoveryController::class, 'runCycle']);
        Route::post('payment-links',      [RecoveryController::class, 'generateLink']);
    });

    // ── Reporting Module ─────────────────────────────────────────────────
    Route::prefix('reports')->middleware('role:SUPER_ADMIN,STORE_MANAGER,ACCOUNTANT,AUDITOR')->group(function () {
        Route::get('profit-loss',         [ReportController::class, 'profitLoss']);
        Route::get('balance-sheet',       [ReportController::class, 'balanceSheet']);
        Route::get('cash-flow',           [ReportController::class, 'cashFlow']);
        Route::get('gst-summary',         [ReportController::class, 'gstSummary']);
    });
});
