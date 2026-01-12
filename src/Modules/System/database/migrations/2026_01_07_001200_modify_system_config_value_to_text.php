<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * 将 system_config 表的 value 字段从 string(1000) 改为 text 类型
     * 以支持存储更长的配置值（如 RSA 私钥等）
     */
    public function up(): void
    {
        if (Schema::hasTable('system_config')) {
            Schema::table('system_config', function (Blueprint $table) {
                $table->text('value')->change()->comment('配置的value');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('system_config')) {
            Schema::table('system_config', function (Blueprint $table) {
                $table->string('value', 1000)->change()->comment('配置的value');
            });
        }
    }
};
