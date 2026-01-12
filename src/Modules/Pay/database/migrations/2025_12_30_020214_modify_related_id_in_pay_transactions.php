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
        if (!Schema::hasTable('pay_transactions')) {
            return;
        }

        // 修改 related_id 字段类型为 string，以支持 UUID 和整数 ID
        Schema::table('pay_transactions', function (Blueprint $table) {
            $table->string('related_id', 36)->nullable()->change()->comment('关联模型ID（多态，支持 UUID 和整数 ID）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('pay_transactions')) {
            return;
        }

        // 回滚为 unsignedBigInteger（注意：如果已有 UUID 数据，回滚会失败）
        Schema::table('pay_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('related_id')->nullable()->change()->comment('关联模型ID（多态）');
        });
    }
};
