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
    public function up(): void
    {

        if (Schema::hasTable('system_connector_log_statistics')) {
            return;
        }

        Schema::create('system_connector_log_statistics', function (Blueprint $table) {
            $table->increments('id')->comment('ID');
            $table->string('path')->comment('接口地址');
            $table->integer('average_time_taken')->default('0')->comment('平均耗时(ms)');
            $table->integer('count')->default('0')->comment('总请求次数');
            $table->integer('fail_count')->default('0')->comment('失败次数');
            $table->integer('success_count')->default('0')->comment('成功次数');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->engine = 'InnoDB';
            $table->comment('接口日志日统计');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('system_connector_log_statistics');
    }
};
