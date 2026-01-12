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
        if (Schema::hasTable('ai_providers')) {
            return;
        }

        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('服务商名称');
            $table->string('logo')->comment('服务商logo');
            $table->string('provider')->comment('服务商提供者');
            $table->string('api_url')->nullable()->comment('接口URL');
            $table->string('app_key')->nullable()->comment('AppKey');
            $table->string('version')->nullable()->comment('版本');
            $table->tinyInteger('status')->default(1)->comment('状态 1 正常 2 停用');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->engine = 'InnoDB';
            $table->comment('ai 提供商');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_providers');
    }
};
