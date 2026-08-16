<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    if (!Schema::hasColumn('product_warehouse', 'stock')) {
        Schema::table('product_warehouse', function (Blueprint $table) {
            $table->integer('stock')->default(0)->after('warehouse_id');
        });
    }
}

public function down()
{
    if (Schema::hasColumn('product_warehouse', 'stock')) {
        Schema::table('product_warehouse', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
}

};
