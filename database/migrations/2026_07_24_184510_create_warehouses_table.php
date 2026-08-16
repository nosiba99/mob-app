<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المستودع
            $table->unsignedBigInteger('area_id')->nullable(); // مستودع تابع لمنطقة
            $table->enum('type', ['main', 'area'])->default('area'); // رئيسي أو مستودع منطقة
            $table->timestamps();

            $table->foreign('area_id')->references('id')->on('areas')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouses');
    }
};
