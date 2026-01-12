<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('ai_knowledge_files')) {
            return;
        }

        Schema::create('ai_knowledge_files', function (Blueprint $table) {
            $table->id()->comment('id');
            $table->integer('knowledge_id')->comment('知识库ID');
            $table->string('filename', 100)->comment('文件名称');
            $table->string('extension', 10)->comment('文档后缀');
            $table->string('content', 5000)->comment('文件内容');
            $table->tinyText('embedding_content')->comment('向量内容');
            $table->creatorId();
            $table->createdAt();
            $table->updatedAt();
            $table->deletedAt();

            $table->engine = 'InnoDB';
            $table->comment('知识库文件');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_knowledge_files');
    }
};
