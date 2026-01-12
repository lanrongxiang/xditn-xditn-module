<?php

use Illuminate\Support\Facades\Route;
use Modules\Openapi\Http\Controllers\OpenapiRequestLogController;
use Modules\Openapi\Http\Controllers\UsersController;

Route::prefix('openapi')->group(function () {

    Route::apiResource('users', UsersController::class)->names('openapi.users');
    Route::put('user/{id}/regenerate', [UsersController::class, 'regenerate'])->name('openapi.users.regenerate');
    Route::post('user/charge', [UsersController::class, 'charge'])->name('openapi.users.charge');

    Route::apiResource('openapi/request/log', OpenapiRequestLogController::class)->names('openapi.request.log');
    // next
});
