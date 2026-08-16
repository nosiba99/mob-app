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
    if (Schema::hasColumn('warehouses', 'area_id')) {
        Schema::table('warehouses', function (Blueprint $table) {
            try {
                $table->dropForeign(['area_id']);
            } catch (\Throwable $e) {
                // SQLite/older drivers may not expose foreign key metadata here.
            }

            $table->dropColumn('area_id');
        });
    }
}

public function down()
{
    if (!Schema::hasColumn('warehouses', 'area_id')) {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('set null');
        });
    }
}

};
