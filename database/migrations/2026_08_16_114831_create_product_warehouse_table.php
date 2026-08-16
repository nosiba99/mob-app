<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_warehouse', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('variant_id');
    $table->foreign('variant_id')
          ->references('id')
          ->on('product_variants')
          ->onDelete('cascade');

    $table->unsignedBigInteger('warehouse_id');
    $table->foreign('warehouse_id')
          ->references('id')
          ->on('warehouses')
          ->onDelete('cascade');

    $table->integer('stock')->default(0);

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_warehouse');
    }
};
