<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('user'); 
        $table->unsignedBigInteger('area_id')->nullable();
        $table->boolean('is_active')->default(true);

        $table->foreign('area_id')->references('id')->on('areas')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['area_id']);
        $table->dropColumn(['role', 'area_id', 'is_active']);
    });
}

};
