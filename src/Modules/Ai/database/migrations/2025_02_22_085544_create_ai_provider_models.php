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
        if (Schema::hasTable('ai_provider_models')) {
            return;
        }

        Schema::create('ai_provider_models', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_id')->comment('提供商ID');
            $table->string('name')->comment('模型ID');
            $table->string('display_name')->comment('模型显示名称');
            $table->integer('max_token')->comment('模型支持最大 token 数');
            $table->tinyInteger('is_support_image')->comment('支持图像:1=支持,2=不支持');
            $table->tinyInteger('status')->default(1)->comment('状态 1 正常 2 停用');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->engine = 'InnoDB';
            $table->comment('ai助手模型');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_provider_models');
    }
};
