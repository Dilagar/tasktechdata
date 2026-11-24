<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseRequisitionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Purchase Requisitions Flow
    |--------------------------------------------------------------------------
    */
    Route::prefix('pr')->name('pr.')->group(function () {
        Route::get('/', [PurchaseRequisitionController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseRequisitionController::class, 'create'])->name('create');
        Route::post('/', [PurchaseRequisitionController::class, 'store'])->name('store');
        Route::get('/{pr}', [PurchaseRequisitionController::class, 'show'])->name('show');
        Route::get('/{pr}/edit', [PurchaseRequisitionController::class, 'edit'])->name('edit');
        Route::put('/{pr}', [PurchaseRequisitionController::class, 'update'])->name('update');
        Route::post('/{pr}/submit', [PurchaseRequisitionController::class, 'submit'])->name('submit');
    });

    /*
    |--------------------------------------------------------------------------
    | Purchase Orders Flow (PR to PO Conversion, Approval, Lifecycle)
    |--------------------------------------------------------------------------
    */
    Route::prefix('po')->name('po.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/create-from-pr', [\App\Http\Controllers\PurchaseOrderController::class, 'createFromPr'])->name('create-from-pr');
        Route::post('/configure', [\App\Http\Controllers\PurchaseOrderController::class, 'configureConversion'])->name('configure');
        Route::post('/', [\App\Http\Controllers\PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{po}', [\App\Http\Controllers\PurchaseOrderController::class, 'show'])->name('show');
        Route::get('/{po}/edit', [\App\Http\Controllers\PurchaseOrderController::class, 'edit'])->name('edit');
        Route::put('/{po}', [\App\Http\Controllers\PurchaseOrderController::class, 'update'])->name('update');
        Route::post('/{po}/submit', [\App\Http\Controllers\PurchaseOrderController::class, 'submit'])->name('submit');
        Route::post('/{po}/cancel', [\App\Http\Controllers\PurchaseOrderController::class, 'cancel'])->name('cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | Budget Control & Ledger Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('budgets')->name('budgets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BudgetController::class, 'index'])->name('index');
        Route::get('/{budget}', [\App\Http\Controllers\BudgetController::class, 'show'])->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Generic Approval Actions & Queue
    |--------------------------------------------------------------------------
    */
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/pending', [ApprovalController::class, 'pending'])->name('pending');
        Route::post('/steps/{step}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/steps/{step}/reject', [ApprovalController::class, 'reject'])->name('reject');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Management Routes (Role Authorization: Admin only)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('role:Admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/cost-centres', [AdminController::class, 'costCentres'])->name('cost_centres');
    });

    /*
    |--------------------------------------------------------------------------
    | Vendor Management Routes (Role Authorization: Admin or Procurement Officer)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('role:Admin,Procurement Officer')->group(function () {
        Route::get('/vendors', [AdminController::class, 'vendors'])->name('vendors');
        Route::get('/vendors/{vendor}', [AdminController::class, 'showVendor'])->name('vendors.show');
    });
});
