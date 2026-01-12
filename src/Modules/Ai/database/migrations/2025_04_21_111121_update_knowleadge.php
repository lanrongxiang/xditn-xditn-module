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

        Schema::table('ai_knowledge_bases', function (Blueprint $table) {
            $table->string('description')->comment('知识库描述')->after('title');
            $table->string('embedding_model')->comment('向量模型')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
