<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('ai_chat_bots')) {
            return;
        }

        Schema::create('ai_chat_bots', function (Blueprint $table) {
            $table->id();
            $table->string('logo');
            $table->string('title');
            $table->string('desc')->comment('描述');
            $table->text('prompt')->comment('prompt');
            $table->tinyInteger('is_use_knowledge')->comment('是否使用知识库')->default(0);
            $table->tinyInteger('contexts')->comment('上下文数量')->default(5);
            $table->unsignedInteger('max_tokens')->comment('最大token数')->default(2000);
            $table->float('temperature')->comment('温度')->default(0);
            $table->float('top_p')->comment('top_p')->default(0.5);
            $table->tinyInteger('status')->default(1)->comment('状态:1=启用,2=禁用');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->engine = 'InnoDB';
            $table->comment('AI 智能体');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_chat_bots');
    }
};
