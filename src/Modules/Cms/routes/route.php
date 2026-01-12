<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CategoryController;
use Modules\Cms\Http\Controllers\FeedbackController;
use Modules\Cms\Http\Controllers\PostController;
use Modules\Cms\Http\Controllers\ResourceController;
use Modules\Cms\Http\Controllers\SettingController;
use Modules\Cms\Http\Controllers\TagController;

Route::prefix('cms')->group(function () {

    Route::adminResource('category', CategoryController::class)->names('cms_category');

    Route::adminResource('post', PostController::class);
    Route::put('post/enable/{id}', [PostController::class, 'enable']);

    Route::adminResource('tag', TagController::class);

    Route::post('setting', [SettingController::class, 'store']);

    // 网站设置相关路由（必须在通用路由之前）
    Route::get('setting/site-config', [SettingController::class, 'siteConfig']);
    Route::get('setting/privacy-policy', [SettingController::class, 'privacyPolicy']);
    Route::get('setting/user-agreement', [SettingController::class, 'userAgreement']);
    Route::get('setting/subscription-terms', [SettingController::class, 'subscriptionTerms']);

    // 通用路由（必须在特定路由之后）
    Route::get('setting/{key?}', [SettingController::class, 'index']);

    Route::adminResource('resource', ResourceController::class);
    Route::put('resource/enable/{id}', [ResourceController::class, 'enable']);

    // 反馈管理
    Route::adminResource('feedback', FeedbackController::class);
    Route::post('feedback/{id}/reply', [FeedbackController::class, 'reply']);
    Route::put('feedback/{id}/status', [FeedbackController::class, 'updateStatus']);

    //next
});
