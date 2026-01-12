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
        if (Schema::hasColumn('members', 'device_id')) {
            return;
        }

        Schema::table('members', function (Blueprint $table) {
            $table->string('device_id', 128)->nullable()->unique()->after('miniapp_openid')->comment('设备ID');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at')->comment('最近登录IP');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'last_login_ip']);
        });
    }
};
