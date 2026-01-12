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
        if (Schema::hasTable('pay_reconciliation_records')) {
            return;
        }

        Schema::create('pay_reconciliation_records', function (Blueprint $table) {
            // 使用 UUID 作为主键
            $table->uuid('id')->primary()->comment('对账记录ID（UUID）');
            $table->date('reconciliation_date')->comment('对账日期');
            $table->tinyInteger('platform')->comment('支付平台:1=支付宝,2=微信,3=银联,4=抖音,5=PayPal,6=ApplePay,7=AppleIAP,8=PromptPay');

            // 对账数据
            $table->unsignedInteger('local_total')->default(0)->comment('本地总金额(分)');
            $table->unsignedInteger('gateway_total')->default(0)->comment('网关总金额(分)');
            $table->integer('difference')->default(0)->comment('差异金额(分)');
            $table->unsignedInteger('local_count')->default(0)->comment('本地交易数');
            $table->unsignedInteger('gateway_count')->default(0)->comment('网关交易数');

            // 明细数据
            $table->json('local_data')->nullable()->comment('本地数据明细(JSON)');
            $table->json('gateway_data')->nullable()->comment('网关数据明细(JSON)');
            $table->json('difference_details')->nullable()->comment('差异明细(JSON)');

            // 状态
            $table->tinyInteger('status')->default(1)->comment('状态:1=待对账,2=已对账,3=有差异,4=已处理');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamp('processed_at')->nullable()->comment('处理时间');
            $table->unsignedBigInteger('creator_id')->nullable()->comment('创建人ID');
            $table->createdAt();
            $table->updatedAt();

            // 索引
            $table->index('reconciliation_date');
            $table->index('platform');
            $table->index('status');

            $table->engine = 'InnoDB';
            $table->comment('对账记录表（从 pay_transactions 对账生成）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_reconciliation_records');
    }
};
