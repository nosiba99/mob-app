<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * صف واحد بس (id = 1) بيمثل حساب المتجر العام.
     */
    public function up(): void
    {
        Schema::create('store_accounts', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        DB::table('store_accounts')->insert([
            'balance'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_accounts');
    }
};
