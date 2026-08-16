<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('area_id');
            $table->string('barcode')->nullable()->after('warehouse_id');
            $table->timestamp('delivered_at')->nullable()->after('barcode');

            $table->foreign('area_id')->references('id')->on('areas')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['area_id', 'warehouse_id', 'barcode', 'delivered_at']);
        });
    }
};
