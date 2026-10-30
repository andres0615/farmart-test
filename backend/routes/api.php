<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DispensationController;
use App\Http\Controllers\DispensationItemController;
use App\Http\Controllers\MinStockController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\PrescriptionItemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\TransferItemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;

Route::get('/enpoint-test', function (Request $request) {
    echo "test-text 2";
    return;
});

Route::middleware('auth:api')->group(function () {
    // rutas protegidas
    
    // Users
    Route::get('/users', [UserController::class, 'index']);

    // Roles
    Route::get('/roles', [RoleController::class, 'index']);

    // Products
    Route::get('/products', [ProductController::class, 'index']);

    // Warehouses
    Route::get('/warehouses', [WarehouseController::class, 'index']);

    // Patients
    Route::get('/patients', [PatientController::class, 'index']);

    // Prescriptions
    Route::get('/prescriptions', [PrescriptionController::class, 'index']);
    Route::get('/prescription-items', [PrescriptionItemController::class, 'index']);

    // Dispensations
    Route::get('/dispensations', [DispensationController::class, 'index']);
    Route::post('/dispensations', [DispensationController::class, 'store']);
    Route::post('/dispensations/preview', [DispensationController::class, 'preview']);
    Route::get('/dispensations/{id}', [DispensationController::class, 'show'])
        ->whereNumber('id');
    Route::get('/dispensation-items', [DispensationItemController::class, 'index']);

    // Transfers
    Route::get('/transfers', [TransferController::class, 'index']);
    Route::get('/transfer-items', [TransferItemController::class, 'index']);

    // Stock
    Route::get('/stock', [StockController::class, 'index']);
    Route::get('/min-stock', [MinStockController::class, 'index']);
    Route::get('/batches', [BatchController::class, 'index']);
    Route::get('/stock-movements', [StockMovementController::class, 'index']);

    // Audit
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});

Route::group([
    'middleware' => 'api',
], function ($router) {

    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);

});
