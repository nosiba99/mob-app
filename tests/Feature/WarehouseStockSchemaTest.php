<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WarehouseStockSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_warehouse_table_has_stock_column()
    {
        $this->assertTrue(Schema::hasColumn('product_warehouse', 'stock'));
    }
}
