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
    Schema::create('warehouse_area', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('warehouse_id');
        $table->unsignedBigInteger('area_id');
        $table->timestamps();

        $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');

        $table->unique(['warehouse_id', 'area_id']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_area');
    }
};
