<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/enpoint-test', function (Request $request) {
    echo "test-text 2";
    return;
});

Route::middleware('auth:api')->group(function () {
    // rutas protegidas
});

Route::group([
    'middleware' => 'api',
], function ($router) {

    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);

});