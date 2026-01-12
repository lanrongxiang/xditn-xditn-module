<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * 将 members 表的 id 字段从自增整数改为 UUID
     * 执行步骤：
     * 1. 添加临时字段存储 UUID
     * 2. 为所有现有记录生成 UUID
     * 3. 更新所有引用 user_id 的表
     * 4. 删除旧 id 字段，重命名临时字段为 id
     */
    public function up(): void
    {
        if (!Schema::hasTable('members')) {
            return;
        }

        // 检查是否已经是 UUID 类型
        // 通过查询第一条记录来判断 ID 格式
        $firstMember = DB::table('members')->first();
        if ($firstMember && isset($firstMember->id)) {
            $idValue = (string) $firstMember->id;
            // UUID 格式：xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx (36 字符)
            if (strlen($idValue) === 36 && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idValue)) {
                // 已经是 UUID 格式，跳过迁移
                return;
            }
        }

        // 1. 添加临时字段存储 UUID
        if (!Schema::hasColumn('members', 'uuid_temp')) {
            Schema::table('members', function (Blueprint $table) {
                $table->uuid('uuid_temp')->nullable()->after('id');
            });
        }

        // 2. 为所有现有记录生成 UUID
        $members = DB::table('members')->get();
        foreach ($members as $member) {
            DB::table('members')
                ->where('id', $member->id)
                ->update(['uuid_temp' => (string) Str::uuid()]);
        }

        // 3. 更新所有引用 user_id 的表（先更新数据，再修改字段类型）
        $this->updateUserIds();

        // 4. 删除 AUTO_INCREMENT 属性（必须先删除，否则无法删除主键）
        // 通过查询表结构来判断是否为自增
        $tableInfo = DB::select('SHOW CREATE TABLE members');
        if (!empty($tableInfo) && isset($tableInfo[0]->{'Create Table'})) {
            $createTable = $tableInfo[0]->{'Create Table'};
            // 检查是否包含 AUTO_INCREMENT
            if (stripos($createTable, 'AUTO_INCREMENT') !== false) {
                // 尝试获取当前 id 字段类型
                $columnInfo = DB::select("SHOW COLUMNS FROM members WHERE Field = 'id'");
                if (!empty($columnInfo) && isset($columnInfo[0]->Type)) {
                    $columnType = $columnInfo[0]->Type;
                    // 根据字段类型移除 AUTO_INCREMENT
                    if (stripos($columnType, 'int') !== false) {
                        // 判断是 int 还是 bigint
                        if (stripos($columnType, 'bigint') !== false) {
                            DB::statement('ALTER TABLE members MODIFY id BIGINT UNSIGNED NOT NULL');
                        } else {
                            DB::statement('ALTER TABLE members MODIFY id INT UNSIGNED NOT NULL');
                        }
                    }
                }
            }
        }

        // 5. 删除旧的主键
        Schema::table('members', function (Blueprint $table) {
            $table->dropPrimary(['id']);
        });

        // 6. 删除旧的 id 字段，重命名 uuid_temp 为 id
        DB::statement('ALTER TABLE members DROP COLUMN id');
        DB::statement('ALTER TABLE members CHANGE uuid_temp id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE members ADD PRIMARY KEY (id)');
    }

    /**
     * Reverse the migrations.
     *
     * 注意：回滚操作需要将 UUID 转换回自增 ID，建议备份数据
     */
    public function down(): void
    {
        if (!Schema::hasTable('members')) {
            return;
        }

        // 回滚操作比较复杂，建议备份数据后重新创建表
        // 这里只提供基本结构
    }

    /**
     * 更新所有引用 user_id 的表.
     */
    protected function updateUserIds(): void
    {
        // 创建 ID 映射
        $mapping = [];
        $members = DB::table('members')->get();
        foreach ($members as $member) {
            $mapping[$member->id] = $member->uuid_temp;
        }

        $tables = [
            'pay_orders',
            'pay_transactions',
            'video_subscriptions',
            'video_watch_records',
            'episode_unlocks',
            'user_wallets',
            'withdrawals',
            'anti_fraud_logs',
            'model_sessions',
            'facebook_pixel_logs',
            'cms_feedbacks',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            // 先更新数据
            foreach ($mapping as $oldId => $newId) {
                DB::table($table)
                    ->where('user_id', $oldId)
                    ->update(['user_id' => $newId]);
            }
        }
    }
};
