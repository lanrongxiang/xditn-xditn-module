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
        if (Schema::hasTable('pay_subscription_orders')) {
            return;
        }

        Schema::create('pay_subscription_orders', function (Blueprint $table) {
            // 使用 UUID 作为主键，关联到 pay_orders
            $table->uuid('id')->primary()->comment('订单ID（UUID，关联 pay_orders.id）');
            $table->unsignedBigInteger('subscription_id')->comment('订阅ID（关联 subscriptions.id）');
            $table->unsignedBigInteger('plan_id')->comment('套餐ID（关联 vip_plans.id）');

            // 订阅订单特有字段
            $table->tinyInteger('order_type')->comment('订单类型:1=首次订阅,2=续费');
            $table->timestamp('started_at')->nullable()->comment('订阅开始时间');
            $table->timestamp('expires_at')->nullable()->comment('订阅过期时间');
            $table->tinyInteger('auto_renew')->default(1)->comment('是否自动续费:1=是,2=否');
            $table->timestamp('next_billing_at')->nullable()->comment('下次扣费时间');

            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            // 索引
            $table->index('subscription_id');
            $table->index('plan_id');
            $table->index('order_type');
            $table->index('expires_at');
            $table->index('next_billing_at');

            $table->engine = 'InnoDB';
            $table->comment('视频订阅订单表（关联 pay_orders）');
        });

        // 在表创建后添加外键约束（确保相关表已存在且主键类型匹配）
        try {
            if (Schema::hasTable('pay_orders')) {
                $tableInfo = DB::select('SHOW CREATE TABLE pay_orders');
                if (!empty($tableInfo)) {
                    $createTable = $tableInfo[0]->{'Create Table'};
                    // 如果 pay_orders 表的主键是 UUID 类型，则添加外键约束
                    if (str_contains($createTable, '`id` char(36)') || str_contains($createTable, '`id` varchar(36)')) {
                        Schema::table('pay_subscription_orders', function (Blueprint $table) {
                            $table->foreign('id')->references('id')->on('pay_orders')->onDelete('cascade');
                        });
                    }
                }
            }

            // 添加其他外键约束（如果相关表存在）
            if (Schema::hasTable('subscriptions')) {
                Schema::table('pay_subscription_orders', function (Blueprint $table) {
                    $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
                });
            }

            if (Schema::hasTable('vip_plans')) {
                Schema::table('pay_subscription_orders', function (Blueprint $table) {
                    $table->foreign('plan_id')->references('id')->on('vip_plans')->onDelete('restrict');
                });
            }
        } catch (\Exception $e) {
            \Log::warning('pay_subscription_orders 表外键约束添加失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_subscription_orders');
    }
};
