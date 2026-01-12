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
        if (!Schema::hasTable('members')) {
            return;
        }

        // 修改 mobile 字段为 nullable，支持自动登录时不需要手机号
        // 注意：不重新添加 unique()，因为字段已经有唯一索引
        Schema::table('members', function (Blueprint $table) {
            $table->string('mobile', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('members')) {
            return;
        }

        // 恢复 mobile 字段为 not null（注意：如果已有 null 值，此操作会失败）
        // 注意：不重新添加 unique()，因为字段已经有唯一索引
        Schema::table('members', function (Blueprint $table) {
            $table->string('mobile', 20)->change();
        });
    }
};
