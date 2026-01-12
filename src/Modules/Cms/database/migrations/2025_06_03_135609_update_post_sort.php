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

        Schema::table('cms_posts', function (Blueprint $table) {
            $table->renameColumn('order', 'sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
