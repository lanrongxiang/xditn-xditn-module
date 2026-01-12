<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('知识库标题');
            $table->integer('sort')->comment('知识库排序');
            $table->tinyInteger('status')->default(1)->comment('状态:1=启用,2=禁用');
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->comment('知识库管理');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_bases');
    }
};
