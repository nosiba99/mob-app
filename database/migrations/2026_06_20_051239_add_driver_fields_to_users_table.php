<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('is_available')->default(true); // متاح ولا لا
        $table->integer('active_orders')->default(0);   // عدد الطلبات الحالية
        $table->string('device_token')->nullable();     // للإشعارات
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['is_available', 'active_orders', 'device_token']);
    });
}

};
