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

        Schema::create('mail_tracking_log', function (Blueprint $table) {
            $table->id();

            // 基础信息
            $table->integer('task_id')->comment('发送任务Id');
            $table->string('recipient')->comment('收件人');

            // 追踪状态
            $table->tinyInteger('is_delivered')->comment('是否送达')->default(0);
            $table->tinyInteger('is_opened')->comment('是否打开')->default(0);
            $table->tinyInteger('is_clicked')->comment('是否点击')->default(0);
            $table->tinyInteger('is_bounced')->comment('是否退回')->default(0);

            // 追踪信息
            $table->string('opened_ip')->nullable()->comment('打开时的IP地址');
            $table->string('clicked_ip')->nullable()->comment('点击时的IP地址');
            $table->text('clicked_url')->nullable()->comment('被点击的链接');
            $table->string('mail_provider')->nullable()->comment('邮件服务商');

            // 时间戳
            $table->timestamp('delivered_at')->nullable()->comment('送达时间');
            $table->timestamp('opened_at')->nullable()->comment('打开时间');
            $table->timestamp('clicked_at')->nullable()->comment('点击时间');
            $table->timestamp('bounced_at')->nullable()->comment('退回时间');

            // 系统字段
            $table->integer('created_at')->comment('创建时间')->default(0);
            $table->integer('updated_at')->comment('更新时间')->default(0);
            $table->integer('deleted_at')->comment('软删除')->default(0);

            // 索引
            $table->index('task_id');
            $table->index('recipient');
            $table->index(['is_delivered', 'is_opened', 'is_clicked']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_tracking_log');
    }
};
