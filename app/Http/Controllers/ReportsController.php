<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Category;

class ReportsController extends Controller
{
    // ---------------------------------------------------------
    // تقارير المستخدمين
    // ---------------------------------------------------------
    public function usersStats()
    {
        return response()->json([
            'total_users' => User::count(),
        ]);
    }

    // ---------------------------------------------------------
    // تقارير الطلبات
    // ---------------------------------------------------------
    public function ordersStats()
    {
        return response()->json([
            'daily_orders'   => Order::whereDate('created_at', today())->count(),
            'weekly_orders'  => Order::whereBetween('created_at', [now()->subWeek(), now()])->count(),
            'monthly_orders' => Order::whereMonth('created_at', now()->month)->count(),
        ]);
    }

    // ---------------------------------------------------------
    // تقارير الأرباح
    // ---------------------------------------------------------
    public function revenueStats()
    {
        return response()->json([
            'weekly_revenue'  => Order::whereBetween('created_at', [now()->subWeek(), now()])->sum('total_price'),
            'monthly_revenue' => Order::whereMonth('created_at', now()->month)->sum('total_price'),
        ]);
    }

    // ---------------------------------------------------------
    // تقارير المستودعات
    // ---------------------------------------------------------
    public function warehouseStats()
    {
        return response()->json([
            'warehouse_profits'      => Warehouse::withSum('orders', 'total_price')->get(),
            'warehouse_orders_count' => Warehouse::withCount('orders')->get(),
            'most_active_warehouse'  => Warehouse::withCount('orders')->orderBy('orders_count', 'desc')->first(),
            'least_active_warehouse' => Warehouse::withCount('orders')->orderBy('orders_count', 'asc')->first(),
        ]);
    }

    // ---------------------------------------------------------
    // تقارير المنتجات
    // ---------------------------------------------------------
    public function productsStats()
{
    return response()->json([
        'top_product'   => Product::withSum('orderItems', 'quantity')
                                  ->orderBy('order_items_sum_quantity', 'desc')
                                  ->first(),

        'least_product' => Product::withSum('orderItems', 'quantity')
                                  ->orderBy('order_items_sum_quantity', 'asc')
                                  ->first(),

        'top_category'  => Category::withCount([
                                'products as total_quantity' => function ($query) {
                                    $query->select(\DB::raw('SUM(order_items.quantity)'))
                                          ->join('order_items', 'products.id', '=', 'order_items.product_id');
                                }
                            ])
                            ->orderBy('total_quantity', 'desc')
                            ->first(),

        'least_category'=> Category::withCount([
                                'products as total_quantity' => function ($query) {
                                    $query->select(\DB::raw('SUM(order_items.quantity)'))
                                          ->join('order_items', 'products.id', '=', 'order_items.product_id');
                                }
                            ])
                            ->orderBy('total_quantity', 'asc')
                            ->first(),
    ]);
}


    // ---------------------------------------------------------
    // تقارير النمو
    // ---------------------------------------------------------
    public function growthStats()
    {
        $currentMonthOrders = Order::whereMonth('created_at', now()->month)->count();
        $lastMonthOrders    = Order::whereMonth('created_at', now()->subMonth()->month)->count();

        $orderGrowth = $lastMonthOrders > 0
            ? (($currentMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100
            : 0;

        $currentMonthRevenue = Order::whereMonth('created_at', now()->month)->sum('total_price');
        $lastMonthRevenue    = Order::whereMonth('created_at', now()->subMonth()->month)->sum('total_price');

        $revenueGrowth = $lastMonthRevenue > 0
            ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        return response()->json([
            'order_growth'        => $orderGrowth,
            'revenue_growth'      => $revenueGrowth,
            'average_order_value' => Order::avg('total_price'),
        ]);
    }
}
