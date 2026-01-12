<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * 将 cms_options 表的 value 字段从 text 改为 longText 类型
     * 以支持存储更长的配置值（如多语言 JSON 内容、隐私政策、用户协议等长文本）
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('cms_options')) {
            Schema::table('cms_options', function (Blueprint $table) {
                $table->longText('value')->change()->comment('value 值');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('cms_options')) {
            Schema::table('cms_options', function (Blueprint $table) {
                $table->text('value')->change()->comment('value 值');
            });
        }
    }
};
