<?php

use Illuminate\Support\Facades\Route;
use Modules\Domain\Http\Controllers\DomainRecordsController;
use Modules\Domain\Http\Controllers\DomainsController;

Route::prefix('domain')->group(function () {

    Route::apiResource('domains', DomainsController::class);
    Route::apiResource('domain/records', DomainRecordsController::class);
    //next
});
