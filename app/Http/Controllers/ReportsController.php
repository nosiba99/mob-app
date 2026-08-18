<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Category;
use App\Models\StoreAccount;
use App\Models\StoreTransaction;

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
    // ⚠️ الأرباح الحقيقية = الطلبات المُسلَّمة فقط (delivered)
    // مش كل الطلبات، لأنو طلب pending أو canceled ما إلو مبلغ محصّل فعلياً
    // ---------------------------------------------------------
    public function revenueStats()
    {
        $delivered = Order::where('status', Order::STATUS_DELIVERED);

        return response()->json([
            'weekly_revenue'  => (clone $delivered)->whereBetween('created_at', [now()->subWeek(), now()])->sum('total_price'),
            'monthly_revenue' => (clone $delivered)->whereMonth('created_at', now()->month)->sum('total_price'),
            'total_revenue'   => (clone $delivered)->sum('total_price'),
        ]);
    }

    // ---------------------------------------------------------
    // حساب المتجر (الرصيد الفعلي المحصّل بعد كل عملية تسليم مؤكدة بالباركود)
    // ---------------------------------------------------------
    public function storeAccount()
    {
        return response()->json([
            'balance' => StoreAccount::account()->balance,
        ]);
    }

    // سجل حركات حساب المتجر (كل عملية تحصيل)
    public function storeTransactions()
    {
        $transactions = StoreTransaction::with('order:id,user_id,total_price,status')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    // ---------------------------------------------------------
    // تقارير المستودعات
    // ⚠️ بردو محصورة بالطلبات المُسلَّمة فقط منشان أرباح حقيقية
    // ---------------------------------------------------------
    public function warehouseStats()
    {
        $deliveredOnly = function ($query) {
            $query->where('status', Order::STATUS_DELIVERED);
        };

        return response()->json([
            'warehouse_profits'      => Warehouse::withSum(['orders as delivered_total' => $deliveredOnly], 'total_price')->get(),
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

        $currentMonthRevenue = Order::where('status', Order::STATUS_DELIVERED)
            ->whereMonth('created_at', now()->month)->sum('total_price');
        $lastMonthRevenue    = Order::where('status', Order::STATUS_DELIVERED)
            ->whereMonth('created_at', now()->subMonth()->month)->sum('total_price');

        $revenueGrowth = $lastMonthRevenue > 0
            ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        return response()->json([
            'order_growth'        => $orderGrowth,
            'revenue_growth'      => $revenueGrowth,
            'average_order_value' => Order::where('status', Order::STATUS_DELIVERED)->avg('total_price'),
        ]);
    }
}
