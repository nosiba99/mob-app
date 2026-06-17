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
    Schema::table('cart_items', function (Blueprint $table) {
        // تحقق قبل الإضافة
        if (!Schema::hasColumn('cart_items', 'variant_id')) {
            $table->foreignId('variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->cascadeOnDelete();
        }
        if (!Schema::hasColumn('cart_items', 'size')) {
            $table->string('size')->nullable();
        }
        if (!Schema::hasColumn('cart_items', 'color')) {
            $table->string('color')->nullable();
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            //
        });
    }
};
