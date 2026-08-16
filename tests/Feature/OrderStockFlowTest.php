<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductWarehouse;
use App\Models\Size;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStockFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_to_cart_and_checkout_with_stock_reduction()
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'phone' => '0999999999',
            'role' => 'user',
        ]);

        $category = Category::create([
            'name' => 'ملابس',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'المستودع الرئيسي',
            'type' => 'main',
        ]);

        $area = Area::create([
            'name' => 'المزة',
            'warehouse_id' => $warehouse->id,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'قميص',
            'description' => 'قميص تجريبي',
            'price' => 100,
            'stock' => 10,
        ]);

        $color = Color::create([
            'name' => 'أحمر',
            'code' => '#ff0000',
        ]);

        $size = Size::create(['name' => 'M']);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
            'stock' => 10,
            'price' => 100,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 10,
        ]);

        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'size_id' => $size->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/user/checkout', [
            'area' => 'المزة',
            'address' => 'عنوان تجريبي',
            'payment_method' => 'cash',
            'notes' => 'ملاحظة',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'area_id' => $area->id,
            'address' => 'عنوان تجريبي',
        ]);

        $stockAfterCheckout = ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first()->stock;

        $this->assertSame(8, $stockAfterCheckout);
    }
}
