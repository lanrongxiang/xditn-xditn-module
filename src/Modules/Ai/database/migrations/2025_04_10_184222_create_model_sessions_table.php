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
        Schema::create('ai_model_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->comment('会话ID');
            $table->string('title')->nullable()->comment('会员标题');
            $table->unsignedBigInteger('user_id')->comment('会话用户ID');
            $table->unsignedBigInteger('bot_id')->comment('会员使用的智能体');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->comment('AI模型会话');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_model_sessions');
    }
};
