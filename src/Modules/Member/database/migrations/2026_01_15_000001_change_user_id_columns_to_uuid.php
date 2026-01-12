<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * 修改所有引用 members.user_id 的表，将 user_id 字段改为 UUID 类型
     */
    public function up(): void
    {
        $tables = [
            'pay_orders',
            'pay_transactions',
            'subscriptions',
            'video_watch_records',
            'episode_unlocks',
            'user_wallets',
            'withdrawals',
            'anti_fraud_logs',
            'model_sessions',
            'facebook_pixel_logs',
            'cms_feedbacks',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'user_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // 修改 user_id 字段为 uuid 类型
                $table->uuid('user_id')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'pay_orders',
            'pay_transactions',
            'subscriptions',
            'video_watch_records',
            'episode_unlocks',
            'user_wallets',
            'withdrawals',
            'anti_fraud_logs',
            'model_sessions',
            'facebook_pixel_logs',
            'cms_feedbacks',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'user_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                // 回滚为 unsignedBigInteger
                $table->unsignedBigInteger('user_id')->change();
            });
        }
    }
};
