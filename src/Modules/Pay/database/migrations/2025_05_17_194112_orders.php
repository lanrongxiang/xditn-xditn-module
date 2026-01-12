<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 如果 pay_orders 表已存在，检查主键类型
        if (Schema::hasTable('pay_orders')) {
            try {
                // 检查主键类型
                $tableInfo = DB::select('SHOW CREATE TABLE pay_orders');
                if (!empty($tableInfo)) {
                    $createTable = $tableInfo[0]->{'Create Table'};
                    // 如果主键是 UUID 类型，直接返回（表已存在且结构正确）
                    if (str_contains($createTable, '`id` char(36)') || str_contains($createTable, '`id` varchar(36)')) {
                        // 表已存在且主键类型正确，直接返回
                        return;
                    }
                    // 如果主键不是 UUID 类型，删除表重新创建
                    // 先删除依赖表
                    Schema::dropIfExists('pay_order_refunds');
                    Schema::dropIfExists('pay_recharge_orders');
                    Schema::dropIfExists('pay_subscription_orders');
                    Schema::dropIfExists('pay_purchase_orders');
                    Schema::dropIfExists('pay_transactions');
                    // 再删除主表
                    Schema::dropIfExists('pay_orders');
                }
            } catch (\Exception $e) {
                // 如果检查失败，尝试删除表
                Schema::dropIfExists('pay_order_refunds');
                Schema::dropIfExists('pay_orders');
            }
        }

        // 基础订单表（通用字段）
        Schema::create('pay_orders', function (Blueprint $table) {
            // 使用 UUID 作为主键，订单号就是 UUID
            $table->uuid('id')->primary()->comment('订单ID（UUID，同时作为订单号）');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->string('out_trade_no', 128)->nullable()->comment('第三方订单号');

            // 支付信息
            $table->tinyInteger('platform')->comment('支付平台:1=支付宝,2=微信,3=银联,4=抖音,5=PayPal,6=ApplePay,7=AppleIAP,8=PromptPay');
            $table->string('action', 20)->nullable()->comment('支付操作:web,app,miniapp等');
            $table->unsignedInteger('amount')->comment('金额(分)');
            $table->string('currency', 10)->default('USD')->comment('货币单位');

            // 状态
            $table->tinyInteger('pay_status')->default(1)->comment('支付状态:1=待支付,2=支付成功,3=支付失败,4=超时未支付');
            $table->tinyInteger('refund_status')->default(0)->comment('退款状态:0=未退款,1=待退款,2=退款成功,3=退款失败,4=拒绝退款');

            // 扩展数据
            $table->json('gateway_data')->nullable()->comment('支付网关返回数据(JSON)');
            $table->text('remark')->nullable()->comment('备注');

            // 时间
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            // 索引
            $table->index('user_id');
            $table->index('out_trade_no');
            $table->index('platform');
            $table->index('pay_status');
            $table->index('refund_status');
            $table->index('created_at');
            $table->index('paid_at');

            $table->engine = 'InnoDB';
            $table->comment('基础订单表（通用字段）');
        });

        // 退款表（整合到 orders.php）
        if (Schema::hasTable('pay_order_refunds')) {
            return;
        }

        Schema::create('pay_order_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('退款ID（UUID）');
            $table->uuid('order_id')->comment('订单ID（UUID）');
            $table->string('refund_no', 64)->unique()->comment('退款单号');
            $table->unsignedInteger('refund_amount')->comment('退款金额(分)');
            $table->string('refund_reason')->nullable()->comment('退款原因');
            $table->string('refuse_reason')->nullable()->comment('拒绝退款原因');
            $table->unsignedBigInteger('applicant_id')->nullable()->comment('申请人ID（后台申请）');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作人ID（后台操作）');
            $table->tinyInteger('refund_status')->default(1)->comment('退款状态:1=待退款,2=退款成功,3=退款失败,4=拒绝退款');
            $table->timestamp('refunded_at')->nullable()->comment('退款时间');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            // 索引
            $table->index('order_id');
            $table->index('refund_no');
            $table->index('refund_status');
            $table->index('created_at');

            // 外键约束
            $table->foreign('order_id')->references('id')->on('pay_orders')->onDelete('cascade');

            $table->engine = 'InnoDB';
            $table->comment('订单退款表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_order_refunds');
        Schema::dropIfExists('pay_orders');
    }
};
