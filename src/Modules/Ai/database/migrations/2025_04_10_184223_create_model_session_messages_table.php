<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_model_session_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->comment('会话ID');
            $table->tinyInteger('sender_type')->comment('发送人类型:1=用户,2=智能体');
            $table->string('sender', 50)->comment('发送人');
            $table->tinyInteger('type')->comment('消息类型:1=文本,2=图片,3=文件,4=链接');
            $table->text('content')->comment('消息内容');
            $table->integer('cost_token')->default(0)->comment('消耗token');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->comment('AI模型会话表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_model_session_messages');
    }
};
