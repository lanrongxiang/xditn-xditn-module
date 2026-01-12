<?php

use Illuminate\Support\Facades\Route;
use Modules\Mail\Http\Controllers\MailTrackingController;
use Modules\Mail\Http\Controllers\SendController;
use Modules\Mail\Http\Controllers\SendTaskController;
use Modules\Mail\Http\Controllers\SettingController;
use Modules\Mail\Http\Controllers\TemplateController;

Route::prefix('mail')->group(function () {
    // 邮件基本设置
    Route::get('setting', [SettingController::class, 'index'])->name('mail.setting.index');
    Route::post('setting', [SettingController::class, 'store'])->name('mail.setting.store');

    Route::adminResource('template', TemplateController::class)->names('mail_template');

    Route::post('send', [SendController::class, 'to']);

    Route::adminResource('send/task', SendTaskController::class)->names('mail_send_task');

    // 邮件追踪
    Route::prefix('mail/track')->name('mail.track.')->group(function () {
        Route::get('open/{tracking_id}', [MailTrackingController::class, 'trackOpen'])
            ->name('open');
        Route::get('click/{tracking_id}', [MailTrackingController::class, 'trackClick'])
            ->name('click');
        Route::post('delivery', [MailTrackingController::class, 'handleDeliveryCallback'])
            ->name('delivery');
    })->withoutMiddleware(config('xditn.route.middlewares'));
    //next
});
