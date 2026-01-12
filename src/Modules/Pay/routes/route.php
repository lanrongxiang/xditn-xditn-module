<?php

use Illuminate\Support\Facades\Route;
use Modules\Pay\Http\Controllers\ConfigController;
use Modules\Pay\Http\Controllers\OrderController;
use Modules\Pay\Http\Controllers\OrderRefundController;
use Modules\Pay\Http\Controllers\RechargeActivityController;
use Modules\Pay\Http\Controllers\ReconciliationController;
use Modules\Pay\Http\Controllers\TransactionController;
use Modules\Pay\Http\Controllers\WalletController;
use Modules\Pay\Http\Controllers\WithdrawalController;

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

    // 钱包管理
    Route::apiResource('wallets', WalletController::class)->only(['index', 'show'])->names('pay_wallets');
    Route::get('wallets/recharge-orders', [WalletController::class, 'rechargeOrders'])->name('pay_wallets.recharge_orders');
    Route::get('wallets/recharge-orders/{id}', [WalletController::class, 'rechargeOrder'])->name('pay_wallets.recharge_order');

    // 交易记录
    Route::get('transactions', [TransactionController::class, 'index'])->name('pay_transactions.index');
    Route::get('transactions/{id}', [TransactionController::class, 'show'])->name('pay_transactions.show');

    // 提现管理
    Route::apiResource('withdrawals', WithdrawalController::class)->only(['index', 'show'])->names('pay_withdrawals');
    Route::post('withdrawals/{id}/approve', [WithdrawalController::class, 'approve'])->name('pay_withdrawals.approve');
    Route::post('withdrawals/{id}/reject', [WithdrawalController::class, 'reject'])->name('pay_withdrawals.reject');
    Route::post('withdrawals/{id}/paid', [WithdrawalController::class, 'paid'])->name('pay_withdrawals.paid');

    // 对账管理
    Route::get('reconciliation', [ReconciliationController::class, 'index'])->name('pay_reconciliation.index');
    Route::post('reconciliation', [ReconciliationController::class, 'reconcile'])->name('pay_reconciliation.reconcile');

    // 充值活动管理
    Route::apiResource('recharge/activities', RechargeActivityController::class)->names('pay_recharge_activities');
    Route::put('recharge/activities/enable/{id}', [RechargeActivityController::class, 'enable'])->name('pay_recharge_activities.enable');
    // next
});
