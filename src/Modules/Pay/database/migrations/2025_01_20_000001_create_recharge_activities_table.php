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
        if (Schema::hasTable('recharge_activities')) {
            return;
        }

        Schema::create('recharge_activities', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->string('title', 255)->comment('活动名称');
            $table->text('description')->nullable()->comment('活动描述');
            $table->tinyInteger('type')->comment('活动类型:1=折扣活动,2=充值档位送金币');
            $table->unsignedInteger('min_amount')->default(0)->comment('最低充值金额(分)');
            $table->unsignedInteger('max_amount')->nullable()->comment('最高充值金额(分)，null表示无上限');
            $table->decimal('discount_rate', 5, 2)->nullable()->comment('折扣率(0-100)，例如99表示99折，仅折扣活动使用');
            $table->unsignedInteger('bonus_coins')->default(0)->comment('赠送金币数，仅充值档位活动使用');
            $table->unsignedInteger('original_coins')->nullable()->comment('原价金币数（用于显示），仅充值档位活动使用');
            $table->timestamp('start_at')->nullable()->comment('活动开始时间');
            $table->timestamp('end_at')->nullable()->comment('活动结束时间');
            $table->tinyInteger('status')->default(1)->comment('状态:1=启用,2=禁用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->index('type');
            $table->index('status');
            $table->index(['start_at', 'end_at']);
            $table->index('sort');

            $table->engine = 'InnoDB';
            $table->comment('充值活动表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recharge_activities');
    }
};
