<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\Product;
use Exception;

class WarehouseService
{
    public function findWarehouseForOrder($productId, $areaId)
    {
        $warehouses = Warehouse::whereHas('areas', function ($q) use ($areaId) {
            $q->where('area_id', $areaId);
        })->get();

        foreach ($warehouses as $warehouse) {
            $product = Product::where('id', $productId)
                ->where('warehouse_id', $warehouse->id)
                ->where('stock', '>', 0)
                ->first();

            if ($product) {
                return $warehouse;
            }
        }

        return $this->findNearestWarehouseWithStock($productId, $areaId);
    }

    public function findNearestWarehouseWithStock($productId, $areaId)
    {
        $nearestWarehouses = $this->getNearestWarehouses($areaId);

        foreach ($nearestWarehouses as $warehouseId) {
            $product = Product::where('id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('stock', '>', 0)
                ->first();

            if ($product) {
                return Warehouse::find($warehouseId);
            }
        }

        return null;
    }

    public function getNearestWarehouses($areaId)
    {
        return [
            1 => [1, 2, 3],
            2 => [2, 1],
            3 => [3, 1, 2],
        ][$areaId] ?? [];
    }

    public function checkStock($productId, $warehouseId, $quantity)
    {
        $product = Product::where('id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$product) {
            throw new Exception("المنتج غير موجود في هذا المستودع.");
        }

        if ($product->stock < $quantity) {
            throw new Exception("الكمية المطلوبة غير متوفرة في المستودع.");
        }

        return true;
    }

    public function reduceStock($productId, $warehouseId, $quantity)
    {
        $product = Product::where('id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$product) return false;

        $product->stock -= $quantity;
        $product->save();

        if ($product->stock <= $product->min_stock) {
            $this->sendLowStockNotification($product, $warehouseId);
        }

        return true;
    }

    public function sendLowStockNotification($product, $warehouseId)
    {
        \Log::warning("⚠️ المنتج {$product->name} في المستودع {$warehouseId} قرب يخلص!");
    }
}
