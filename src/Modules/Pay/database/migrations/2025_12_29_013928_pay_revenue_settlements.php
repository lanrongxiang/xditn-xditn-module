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
        if (Schema::hasTable('pay_revenue_settlements')) {
            return;
        }

        Schema::create('pay_revenue_settlements', function (Blueprint $table) {
            // 使用 UUID 作为主键
            $table->uuid('id')->primary()->comment('结算ID（UUID）');
            $table->date('settlement_date')->unique()->comment('结算日期');

            // 订单统计
            $table->unsignedInteger('recharge_count')->default(0)->comment('充值订单数');
            $table->unsignedInteger('recharge_amount')->default(0)->comment('充值金额(分)');
            $table->unsignedInteger('subscription_count')->default(0)->comment('订阅订单数');
            $table->unsignedInteger('subscription_revenue')->default(0)->comment('订阅收入(分)');
            $table->unsignedInteger('purchase_count')->default(0)->comment('购买订单数');
            $table->unsignedInteger('purchase_revenue')->default(0)->comment('购买收入(分)');
            $table->unsignedInteger('renewal_count')->default(0)->comment('续费订单数');
            $table->unsignedInteger('renewal_revenue')->default(0)->comment('续费收入(分)');

            // 退款统计
            $table->unsignedInteger('refund_count')->default(0)->comment('退款订单数');
            $table->unsignedInteger('refund_amount')->default(0)->comment('退款金额(分)');

            // 汇总
            $table->unsignedInteger('total_revenue')->default(0)->comment('总收入(分)');
            $table->unsignedInteger('net_revenue')->default(0)->comment('净收入(分)');
            $table->string('currency', 10)->default('USD')->comment('货币单位');

            // 支付网关明细
            $table->json('gateway_breakdown')->nullable()->comment('按支付网关分类的明细(JSON)');

            // 状态
            $table->tinyInteger('status')->default(1)->comment('状态:1=待确认,2=已确认,3=已对账');
            $table->timestamp('confirmed_at')->nullable()->comment('确认时间');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            // 索引
            $table->index('settlement_date');
            $table->index('status');

            $table->engine = 'InnoDB';
            $table->comment('收入结算表（从 pay_orders 统计生成）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_revenue_settlements');
    }
};
