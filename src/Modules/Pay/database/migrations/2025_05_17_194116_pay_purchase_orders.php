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
        if (Schema::hasTable('pay_purchase_orders')) {
            return;
        }

        Schema::create('pay_purchase_orders', function (Blueprint $table) {
            // 使用 UUID 作为主键，关联到 pay_orders
            $table->uuid('id')->primary()->comment('订单ID（UUID，关联 pay_orders.id）');
            $table->unsignedBigInteger('episode_id')->comment('视频剧集ID（关联 video_episodes.id）');
            $table->unsignedBigInteger('video_id')->comment('视频ID（关联 videos.id）');

            // 购买订单特有字段
            $table->unsignedInteger('coins')->comment('消耗金币数');
            $table->tinyInteger('purchase_type')->comment('购买类型:1=单集购买,2=整剧购买');

            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            // 索引
            $table->index('episode_id');
            $table->index('video_id');
            $table->index('purchase_type');

            $table->engine = 'InnoDB';
            $table->comment('视频购买订单表（关联 pay_orders）');
        });

        // 在表创建后添加外键约束（确保相关表已存在且主键类型匹配）
        try {
            if (Schema::hasTable('pay_orders')) {
                $tableInfo = DB::select('SHOW CREATE TABLE pay_orders');
                if (!empty($tableInfo)) {
                    $createTable = $tableInfo[0]->{'Create Table'};
                    // 如果 pay_orders 表的主键是 UUID 类型，则添加外键约束
                    if (str_contains($createTable, '`id` char(36)') || str_contains($createTable, '`id` varchar(36)')) {
                        Schema::table('pay_purchase_orders', function (Blueprint $table) {
                            $table->foreign('id')->references('id')->on('pay_orders')->onDelete('cascade');
                        });
                    }
                }
            }

            // 添加其他外键约束（如果相关表存在）
            if (Schema::hasTable('video_episodes')) {
                Schema::table('pay_purchase_orders', function (Blueprint $table) {
                    $table->foreign('episode_id')->references('id')->on('video_episodes')->onDelete('restrict');
                });
            }

            if (Schema::hasTable('videos')) {
                Schema::table('pay_purchase_orders', function (Blueprint $table) {
                    $table->foreign('video_id')->references('id')->on('videos')->onDelete('restrict');
                });
            }
        } catch (\Exception $e) {
            \Log::warning('pay_purchase_orders 表外键约束添加失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_purchase_orders');
    }
};
