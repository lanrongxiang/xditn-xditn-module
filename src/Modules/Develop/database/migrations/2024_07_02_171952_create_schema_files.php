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
        if (Schema::hasTable('schema_files')) {
            return;
        }

        Schema::create('schema_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('schema_id');
            $table->text('controller_path')->nullable()->comment('控制器文件路径');
            $table->text('model_path')->nullable()->comment('模型文件路径');
            $table->text('request_path')->nullable()->comment('请求文件路径');
            $table->text('controller_file')->nullable()->comment('控制器文件内容');
            $table->text('model_file')->nullable()->comment('模型文件内容');
            $table->text('request_file')->nullable()->comment('请求文件内容');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schema_files');
    }
};
