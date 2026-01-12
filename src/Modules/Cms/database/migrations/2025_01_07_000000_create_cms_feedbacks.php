<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('cms_feedbacks')) {
            return;
        }

        Schema::create('cms_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户ID（可选，未登录用户也可以反馈）');
            $table->string('type', 20)->comment('反馈类型：bug、suggestion、other');
            $table->string('title', 200)->comment('反馈标题');
            $table->text('content')->comment('反馈内容');
            $table->string('contact', 100)->nullable()->comment('联系方式');
            $table->json('images')->nullable()->comment('反馈图片URL数组');
            $table->string('status', 20)->default('pending')->comment('状态：pending（待处理）、processing（处理中）、resolved（已解决）、closed（已关闭）');
            $table->text('reply')->nullable()->comment('管理员回复');
            $table->unsignedInteger('replied_at')->nullable()->comment('回复时间');
            $table->unsignedBigInteger('replied_by')->nullable()->comment('回复人ID');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index('user_id');
            $table->index('type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_feedbacks');
    }
};
