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
        if (Schema::hasTable('pay_transactions')) {
            return;
        }

        Schema::create('pay_transactions', function (Blueprint $table) {
            // 使用 UUID 作为主键
            $table->uuid('id')->primary()->comment('交易ID（UUID）');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->string('transaction_no', 64)->unique()->comment('交易单号');

            // 交易类型
            $table->string('type', 32)->comment('交易类型:recharge=充值,withdraw=提现,refund=退款,payment=支付,coin_recharge=金币充值,coin_consume=金币消费,coin_refund=金币退款,coin_bonus=金币赠送');
            $table->string('currency_type', 10)->default('fiat')->comment('货币类型:fiat=法币,coin=金币');
            $table->unsignedInteger('amount')->comment('金额(分或金币)');
            $table->string('currency', 10)->nullable()->comment('货币单位(法币时使用):USD,THB,CNY等');
            $table->tinyInteger('direction')->comment('方向:1=收入,2=支出');

            // 余额（金币交易时使用）
            $table->unsignedInteger('balance_before')->nullable()->comment('操作前余额(金币交易时使用)');
            $table->unsignedInteger('balance_after')->nullable()->comment('操作后余额(金币交易时使用)');

            // 关联订单（使用 UUID）
            $table->uuid('order_id')->nullable()->comment('关联订单ID(UUID)');
            $table->string('order_no', 64)->nullable()->comment('关联订单号（冗余字段，便于查询）');

            // 多态关联（关联到不同的业务模型）
            // 使用 string 类型以支持 UUID 和整数 ID
            $table->string('related_type', 100)->nullable()->comment('关联模型类型（多态）');
            $table->string('related_id', 36)->nullable()->comment('关联模型ID（多态，支持 UUID 和整数 ID）');

            // 其他
            $table->string('description', 255)->nullable()->comment('描述');
            $table->json('extra_data')->nullable()->comment('扩展数据(JSON)');
            $table->timestamp('transaction_at')->nullable()->comment('交易时间');
            $table->createdAt();
            $table->updatedAt();

            // 索引
            $table->index('user_id');
            $table->index('transaction_no');
            $table->index('type');
            $table->index('currency_type');
            $table->index('direction');
            $table->index('order_id');
            $table->index('order_no');
            $table->index(['related_type', 'related_id']);
            $table->index('transaction_at');
            $table->index('created_at');

            // 外键约束
            $table->foreign('order_id')->references('id')->on('pay_orders')->onDelete('set null');

            $table->engine = 'InnoDB';
            $table->comment('统一资金明细表（法币和金币交易流水）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_transactions');
    }
};
