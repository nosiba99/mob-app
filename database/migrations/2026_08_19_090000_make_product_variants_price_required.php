<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) أي فاريانت سعرو NULL، منعبيلو سعر المنتج الأساسي كقيمة بديلة مؤقتة
        DB::statement('
            UPDATE product_variants pv
            JOIN products p ON p.id = pv.product_id
            SET pv.price = p.price
            WHERE pv.price IS NULL
        ');

        // 2) لو ضل في فاريانتات بدون سعر (منتجها كمان بدون سعر)، منحطلها 0 مؤقتاً
        //    بس هاد سيناريو نادر ولازم الأدمن يراجعه يدوياً بعدين
        DB::table('product_variants')->whereNull('price')->update(['price' => 0]);

        // 3) منمنع تكرار المشكلة مستقبلاً: العمود يصير إجباري
        DB::statement('ALTER TABLE product_variants MODIFY price DECIMAL(10,2) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE product_variants MODIFY price DECIMAL(10,2) NULL');
    }
};
