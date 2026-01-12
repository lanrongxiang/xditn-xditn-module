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
        if (Schema::hasTable('pay_recharge_orders')) {
            return;
        }

        Schema::create('pay_recharge_orders', function (Blueprint $table) {
            // 使用 UUID 作为主键，关联到 pay_orders
            $table->uuid('id')->primary()->comment('订单ID（UUID，关联 pay_orders.id）');
            $table->unsignedBigInteger('activity_id')->nullable()->comment('充值活动ID（关联 recharge_activities.id）');

            // 充值订单特有字段
            $table->unsignedInteger('coins')->comment('获得金币数');
            $table->unsignedInteger('bonus_coins')->default(0)->comment('赠送金币数');
            $table->unsignedInteger('exchange_rate')->default(100)->comment('汇率(1元=100金币)');

            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            // 索引
            $table->index('activity_id');

            $table->engine = 'InnoDB';
            $table->comment('充值订单表（关联 pay_orders）');
        });

        // 在表创建后添加外键约束（确保 pay_orders 表已存在且主键类型匹配）
        try {
            if (Schema::hasTable('pay_orders')) {
                $tableInfo = DB::select('SHOW CREATE TABLE pay_orders');
                if (!empty($tableInfo)) {
                    $createTable = $tableInfo[0]->{'Create Table'};
                    // 如果 pay_orders 表的主键是 UUID 类型，则添加外键约束
                    if (str_contains($createTable, '`id` char(36)') || str_contains($createTable, '`id` varchar(36)')) {
                        Schema::table('pay_recharge_orders', function (Blueprint $table) {
                            $table->foreign('id')->references('id')->on('pay_orders')->onDelete('cascade');
                        });
                    }
                }
            }

            // 添加 activity_id 外键约束（如果 recharge_activities 表存在）
            if (Schema::hasTable('recharge_activities')) {
                Schema::table('pay_recharge_orders', function (Blueprint $table) {
                    $table->foreign('activity_id')->references('id')->on('recharge_activities')->onDelete('set null');
                });
            }
        } catch (\Exception $e) {
            \Log::warning('pay_recharge_orders 表外键约束添加失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_recharge_orders');
    }
};
