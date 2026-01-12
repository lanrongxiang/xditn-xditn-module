<?php

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\ChatBotsController;
use Modules\Ai\Http\Controllers\KnowledgeBaseController;
use Modules\Ai\Http\Controllers\KnowledgeFilesController;
use Modules\Ai\Http\Controllers\ModelsController;
use Modules\Ai\Http\Controllers\ModelSessionController;
use Modules\Ai\Http\Controllers\ModelSessionMessagesController;
use Modules\Ai\Http\Controllers\ProvidersController;

Route::prefix('ai')->group(function () {
    // 服务商
    Route::adminResource('providers', ProvidersController::class)->names('providers');
    // 模型
    Route::adminResource('provider/models', ModelsController::class)->names('provider_models');
    // 智能体
    Route::adminResource('chat/bots', ChatBotsController::class)->names('chat_bots');
    // 模型会话
    Route::adminResource('model/session', ModelSessionController::class)->names('model_session');
    // 模型会消息
    Route::adminResource('model/session/messages', ModelSessionMessagesController::class)->names('model_session_messages');
    // 知识库
    Route::adminResource('knowledge/base', KnowledgeBaseController::class)->names('knowledge_base');
    // 知识库文件
    Route::adminResource('knowledge/files', KnowledgeFilesController::class)->names('knowledge_files');
    // 解析知识库文件
    Route::post('knowledge/parse/files', [KnowledgeFilesController::class, 'parseFiles']);
    // next
});
