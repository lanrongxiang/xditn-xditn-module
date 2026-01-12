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
        if (Schema::hasTable('mail_send_tasks')) {
            return;
        }

        Schema::create('mail_send_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('from_address')->comment('发件人邮箱');
            $table->string('subject')->comment('邮件主题');
            $table->integer('template_id')->comment('模板ID');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->integer('send_at')->default(0)->comment('发送时间:0=立即发送,1=发送时间');
            $table->text('recipients')->comment('收件人邮箱');
            $table->integer('recipients_num')->default(0)->comment('收件人数量');
            $table->integer('success_num')->default(0)->comment('成功数量');
            $table->integer('failure_num')->default(0)->comment('失败数量');
            $table->tinyInteger('status')->default(0)->comment('状态:0=未开始,1=进行中,2=已完成');
            $table->tinyInteger('is_tracking')->default(0)->comment('是否开启邮件追踪功能');
            $table->integer('finished_at')->default(0)->comment('完成时间');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->engine = 'InnoDB';
            $table->comment('邮件发送任务');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_send_tasks');
    }
};
