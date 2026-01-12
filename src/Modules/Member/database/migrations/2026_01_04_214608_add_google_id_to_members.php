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

        Schema::table('members', function (Blueprint $table) {
            $table->string('google_id', 100)->nullable()->unique()->comment('Google账号ID')->after('miniapp_openid');
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

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
