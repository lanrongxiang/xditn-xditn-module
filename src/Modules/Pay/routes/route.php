<?php

use Illuminate\Support\Facades\Route;
use Modules\Pay\Http\Controllers\ConfigController;
use Modules\Pay\Http\Controllers\OrderController;
use Modules\Pay\Http\Controllers\OrderRefundController;
use Modules\Pay\Http\Controllers\RechargeActivityController;
use Modules\Pay\Http\Controllers\TransactionController;

Route::prefix('pay')->group(function () {
    // 获取配置
    Route::get('config/{driver}', [ConfigController::class, 'show']);
    // 保存配置
    Route::post('config', [ConfigController::class, 'store']);

    Route::adminResource('order', OrderController::class);
    // 申请退款单
    Route::post('order/{id}/refund', [OrderController::class, 'refund']);

    Route::adminResource('order/refund', OrderRefundController::class);
    // 同意退款
    Route::post('order/refund/{id}/agree', [OrderRefundController::class, 'agree']);

    // 交易记录
    Route::get('transactions', [TransactionController::class, 'index'])->name('pay_transactions.index');
    Route::get('transactions/{id}', [TransactionController::class, 'show'])->name('pay_transactions.show');

    // 充值活动管理
    Route::apiResource('recharge/activities', RechargeActivityController::class)->names('pay_recharge_activities');
    Route::put('recharge/activities/enable/{id}', [RechargeActivityController::class, 'enable'])->name('pay_recharge_activities.enable');
    // next
});
